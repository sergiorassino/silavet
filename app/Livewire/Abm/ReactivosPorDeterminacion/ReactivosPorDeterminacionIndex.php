<?php

namespace App\Livewire\Abm\ReactivosPorDeterminacion;

use App\Models\Reactivo;
use App\Models\Reactivoxdeterminacion;
use App\Models\Tipodeterminacion;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class ReactivosPorDeterminacionIndex extends Component
{
    public string $busquedaDeterminacion = '';

    public ?int $idDeterminacionSeleccionada = null;

    /** @var array<int, array{idReactivos: int, nombre: string, cantidad: string}> keyed by reactivoxdeterminacion.id */
    public array $filas = [];

    public bool $modalAgregarAbierto = false;

    public int $nuevoIdReactivo = 0;

    public string $nuevaCantidad = '';

    /** @var int|null Id de fila en edición inline */
    public ?int $filaEditandoId = null;

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);
        abort_unless(Schema::hasTable('reactivoxdeterminacion'), 404, 'Tabla reactivoxdeterminacion no disponible.');

        $primera = Tipodeterminacion::query()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->value('idTipodeterminaciones');

        if ($primera !== null) {
            $this->seleccionarDeterminacion((int) $primera);
        }
    }

    public function seleccionarDeterminacion(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        Tipodeterminacion::query()->findOrFail($id);
        $this->idDeterminacionSeleccionada = $id;
        $this->cerrarModalAgregar();
        $this->filaEditandoId = null;
        $this->sincronizarFilasDesdeBd();
    }

    public function updatedBusquedaDeterminacion(): void
    {
        $this->idDeterminacionSeleccionada = null;
        $this->filas = [];
        $this->cerrarModalAgregar();
        $this->filaEditandoId = null;
    }

    public function abrirModalAgregar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        if ($this->idDeterminacionSeleccionada === null) {
            $this->dispatch('vl-swal-error', mensaje: 'Seleccione una determinación primero.');
            return;
        }

        $this->nuevoIdReactivo = 0;
        $this->nuevaCantidad   = '';
        $this->modalAgregarAbierto = true;
    }

    public function cerrarModalAgregar(): void
    {
        $this->modalAgregarAbierto = false;
        $this->nuevoIdReactivo = 0;
        $this->nuevaCantidad   = '';
    }

    public function agregarReactivo(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        $key = 'rxd-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $idDet = $this->idDeterminacionSeleccionada;
        if ($idDet === null) {
            return;
        }

        $validated = validator(
            ['idReactivos' => $this->nuevoIdReactivo, 'cantidad' => $this->nuevaCantidad],
            [
                'idReactivos' => ['required', 'integer', 'min:1'],
                'cantidad'    => ['required', 'numeric', 'min:0.0001', 'max:9999.9999'],
            ],
            [
                'idReactivos.required' => 'Seleccione un reactivo.',
                'idReactivos.min'      => 'Seleccione un reactivo.',
                'cantidad.required'    => 'La cantidad es obligatoria.',
                'cantidad.numeric'     => 'La cantidad debe ser un número.',
                'cantidad.min'         => 'La cantidad debe ser mayor a 0.',
            ]
        )->validate();

        Reactivo::query()->findOrFail((int) $validated['idReactivos']);

        $yaExiste = Reactivoxdeterminacion::query()
            ->where('idTipodeterminaciones', $idDet)
            ->where('idReactivos', (int) $validated['idReactivos'])
            ->exists();

        if ($yaExiste) {
            $this->dispatch('vl-swal-error', mensaje: 'Ese reactivo ya está asociado a esta determinación.');
            return;
        }

        Reactivoxdeterminacion::query()->create([
            'idTipodeterminaciones' => $idDet,
            'idReactivos'           => (int) $validated['idReactivos'],
            'cantidad'              => round((float) $validated['cantidad'], 4),
        ]);

        $this->sincronizarFilasDesdeBd();
        $this->cerrarModalAgregar();
        $this->dispatch('vl-swal-exito', mensaje: 'Reactivo agregado a la determinación.');
    }

    public function editarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);
        $this->filaEditandoId = $id;
    }

    public function cancelarEdicion(): void
    {
        $this->filaEditandoId = null;
        $this->sincronizarFilasDesdeBd();
    }

    public function guardarCantidad(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        $key = 'rxd-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $fila = $this->filas[$id] ?? null;
        if ($fila === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el renglón.');
            return;
        }

        $validated = validator(
            ['cantidad' => $fila['cantidad']],
            ['cantidad' => ['required', 'numeric', 'min:0.0001', 'max:9999.9999']],
            [
                'cantidad.required' => 'La cantidad es obligatoria.',
                'cantidad.numeric'  => 'La cantidad debe ser un número.',
                'cantidad.min'      => 'La cantidad debe ser mayor a 0.',
            ]
        )->validate();

        $registro = $this->filaDeLaDeterminacionSeleccionada($id);
        $registro->update(['cantidad' => round((float) $validated['cantidad'], 4)]);

        $this->filaEditandoId = null;
        $this->sincronizarFilasDesdeBd();
        $this->dispatch('vl-swal-exito', mensaje: 'Cantidad actualizada.');
    }

    public function eliminarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        $key = 'rxd-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        $this->filaDeLaDeterminacionSeleccionada($id)->delete();
        unset($this->filas[$id]);

        $this->dispatch('vl-swal-exito', mensaje: 'Reactivo quitado de la determinación.');
    }

    public function render()
    {
        $term = trim(mb_strtolower($this->busquedaDeterminacion));

        $determinaciones = Tipodeterminacion::query()
            ->withCount('consumosReactivos')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(nombre) LIKE ?', ["%{$term}%"]);
                });
            })
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $determinacionActiva = $this->idDeterminacionSeleccionada !== null
            ? Tipodeterminacion::query()->find($this->idDeterminacionSeleccionada)
            : null;

        return view('livewire.abm.reactivos-por-determinacion.reactivos-por-determinacion-index', [
            'determinaciones'       => $determinaciones,
            'determinacionActiva'   => $determinacionActiva,
            'reactivosDisponibles'  => $this->reactivosDisponibles(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function sincronizarFilasDesdeBd(): void
    {
        $idDet = $this->idDeterminacionSeleccionada;
        if ($idDet === null) {
            $this->filas = [];
            return;
        }

        $this->filas = Reactivoxdeterminacion::query()
            ->with('reactivo')
            ->where('idTipodeterminaciones', $idDet)
            ->get()
            ->mapWithKeys(function ($fila) {
                return [(int) $fila->id => [
                    'idReactivos' => (int) $fila->idReactivos,
                    'nombre'      => (string) ($fila->reactivo?->reactivo ?? '—'),
                    'cantidad'    => number_format((float) $fila->cantidad, 4, '.', ''),
                ]];
            })
            ->all();
    }

    /** @return Collection<int, Reactivo> */
    private function reactivosDisponibles(): Collection
    {
        $idDet = $this->idDeterminacionSeleccionada;

        $yaAsociados = $idDet !== null
            ? Reactivoxdeterminacion::query()
                ->where('idTipodeterminaciones', $idDet)
                ->pluck('idReactivos')
                ->all()
            : [];

        return Reactivo::query()
            ->when(! empty($yaAsociados), fn ($q) => $q->whereNotIn('id', $yaAsociados))
            ->orderBy('reactivo')
            ->get();
    }

    private function filaDeLaDeterminacionSeleccionada(int $id): Reactivoxdeterminacion
    {
        return Reactivoxdeterminacion::query()
            ->where('idTipodeterminaciones', $this->idDeterminacionSeleccionada)
            ->whereKey($id)
            ->firstOrFail();
    }
}
