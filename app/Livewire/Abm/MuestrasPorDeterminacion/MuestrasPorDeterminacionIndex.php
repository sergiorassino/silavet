<?php

namespace App\Livewire\Abm\MuestrasPorDeterminacion;

use App\Models\Requerimiento;
use App\Models\Reqxtipodet;
use App\Models\Tipodeterminacion;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class MuestrasPorDeterminacionIndex extends Component
{
    public string $busquedaProcedimiento = '';

    public ?int $idRequerimientoSeleccionado = null;

    /** @var list<array{id: int, idTipodeterminaciones: int, orden: int, nombre: string}> */
    public array $vinculos = [];

    public bool $modalAgregarAbierto = false;

    public string $busquedaDeterminacion = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);
        abort_unless(Schema::hasTable('requerimientos'), 404, 'La tabla de requerimientos no está disponible.');
        abort_unless(Schema::hasTable('reqxtipodet'), 404, 'La tabla reqxtipodet no está disponible.');

        $primero = Requerimiento::query()->value('id');

        if ($primero !== null) {
            $this->seleccionarProcedimiento((int) $primero);
        }
    }

    public function seleccionarProcedimiento(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        Requerimiento::query()->findOrFail($id);
        $this->idRequerimientoSeleccionado = $id;
        $this->modalAgregarAbierto = false;
        $this->busquedaDeterminacion = '';
        $this->sincronizarVinculosDesdeBd();
    }

    public function updatedBusquedaProcedimiento(): void
    {
        $this->limpiarSeleccionProcedimiento();
    }

    public function abrirModalAgregar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($this->idRequerimientoSeleccionado === null) {
            $this->dispatch('vl-swal-error', mensaje: 'Seleccione un procedimiento primero.');

            return;
        }

        $this->busquedaDeterminacion = '';
        $this->modalAgregarAbierto = true;
    }

    public function cerrarModalAgregar(): void
    {
        $this->modalAgregarAbierto = false;
        $this->busquedaDeterminacion = '';
    }

    public function agregarDeterminacion(int $idTipo): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'muestras-det-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $idReq = $this->idRequerimientoSeleccionado;
        if ($idReq === null) {
            return;
        }

        Requerimiento::query()->findOrFail($idReq);
        Tipodeterminacion::query()->findOrFail($idTipo);

        $yaExiste = Reqxtipodet::query()
            ->where('idRequerimientos', $idReq)
            ->where('idTipodeterminaciones', $idTipo)
            ->exists();

        if ($yaExiste) {
            $this->dispatch('vl-swal-error', mensaje: 'Esa determinación ya está asociada a este procedimiento.');

            return;
        }

        Reqxtipodet::query()->create([
            'idRequerimientos' => $idReq,
            'idTipodeterminaciones' => $idTipo,
        ]);

        $this->sincronizarVinculosDesdeBd();
        $this->dispatch('vl-swal-exito', mensaje: 'Determinación asociada al procedimiento.');
    }

    public function quitarVinculo(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'muestras-det-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        $this->vinculoDelProcedimientoSeleccionado($id)->delete();
        $this->sincronizarVinculosDesdeBd();

        $this->dispatch('vl-swal-exito', mensaje: 'Determinación desasociada del procedimiento.');
    }

    public function render()
    {
        $term = trim(mb_strtolower($this->busquedaProcedimiento));

        $procedimientos = Requerimiento::query()
            ->withCount('vinculosTipodeterminacion')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(titulo) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(requerimiento) LIKE ?', ["%{$term}%"]);
                });
            })
            ->get();

        $procedimientoActivo = $this->idRequerimientoSeleccionado !== null
            ? Requerimiento::query()->find($this->idRequerimientoSeleccionado)
            : null;

        return view('livewire.abm.muestras-por-determinacion.muestras-por-determinacion-index', [
            'procedimientos' => $procedimientos,
            'procedimientoActivo' => $procedimientoActivo,
            'determinacionesDisponibles' => $this->determinacionesDisponiblesParaAgregar(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function limpiarSeleccionProcedimiento(): void
    {
        $this->idRequerimientoSeleccionado = null;
        $this->vinculos = [];
        $this->modalAgregarAbierto = false;
        $this->busquedaDeterminacion = '';
    }

    /** @return Collection<int, Tipodeterminacion> */
    private function determinacionesDisponiblesParaAgregar(): Collection
    {
        if ($this->idRequerimientoSeleccionado === null) {
            return collect();
        }

        $idsAsociadas = Reqxtipodet::query()
            ->where('idRequerimientos', $this->idRequerimientoSeleccionado)
            ->pluck('idTipodeterminaciones');

        $term = trim(mb_strtolower($this->busquedaDeterminacion));

        return Tipodeterminacion::query()
            ->when($idsAsociadas->isNotEmpty(), fn ($q) => $q->whereNotIn('idTipodeterminaciones', $idsAsociadas))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(nombre) LIKE ?', ["%{$term}%"])
                        ->orWhere('orden', 'like', "%{$term}%");
                });
            })
            ->orderBy('orden')
            ->orderByRaw('LOWER(nombre)')
            ->limit(100)
            ->get(['idTipodeterminaciones', 'orden', 'nombre']);
    }

    private function sincronizarVinculosDesdeBd(): void
    {
        if ($this->idRequerimientoSeleccionado === null) {
            $this->vinculos = [];

            return;
        }

        $this->vinculos = Reqxtipodet::query()
            ->with('tipodeterminacion')
            ->where('idRequerimientos', $this->idRequerimientoSeleccionado)
            ->get()
            ->sortBy(function (Reqxtipodet $vinculo): string {
                $orden = (int) ($vinculo->tipodeterminacion?->orden ?? 0);
                $nombre = mb_strtolower((string) ($vinculo->tipodeterminacion?->nombre ?? ''));

                return sprintf('%05d-%s', $orden, $nombre);
            })
            ->values()
            ->map(fn (Reqxtipodet $vinculo) => [
                'id' => (int) $vinculo->id,
                'idTipodeterminaciones' => (int) $vinculo->idTipodeterminaciones,
                'orden' => (int) ($vinculo->tipodeterminacion?->orden ?? 0),
                'nombre' => (string) ($vinculo->tipodeterminacion?->nombre ?? '—'),
            ])
            ->all();
    }

    private function vinculoDelProcedimientoSeleccionado(int $id): Reqxtipodet
    {
        $idReq = $this->idRequerimientoSeleccionado;
        abort_if($idReq === null, 404);

        return Reqxtipodet::query()
            ->whereKey($id)
            ->where('idRequerimientos', $idReq)
            ->firstOrFail();
    }
}
