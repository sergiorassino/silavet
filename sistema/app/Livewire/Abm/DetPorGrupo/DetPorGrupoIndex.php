<?php

namespace App\Livewire\Abm\DetPorGrupo;

use App\Models\Itemsinforme;
use App\Models\Renglonesxdeterminacion;
use App\Models\Tipodeterminacion;
use App\Support\Itemsinforme\ItemsinformeCatalog;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class DetPorGrupoIndex extends Component
{
    public string $busquedaDeterminacion = '';

    /** @var 'orden'|'nombre' */
    public string $ordenListadoDeterminacion = 'orden';

    public ?int $idDeterminacionSeleccionada = null;

    /** @var array<int, array<string, mixed>> */
    public array $filasRenglon = [];

    public bool $modalAgregarAbierto = false;

    public string $busquedaItem = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

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
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        Tipodeterminacion::query()->findOrFail($id);
        $this->idDeterminacionSeleccionada = $id;
        $this->modalAgregarAbierto = false;
        $this->busquedaItem = '';
        $this->sincronizarFilasDesdeBd();
    }

    public function updatedBusquedaDeterminacion(): void
    {
        $this->limpiarSeleccionDeterminacion();
    }

    private function limpiarSeleccionDeterminacion(): void
    {
        $this->idDeterminacionSeleccionada = null;
        $this->filasRenglon = [];
        $this->modalAgregarAbierto = false;
        $this->busquedaItem = '';
    }

    public function abrirModalAgregar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($this->idDeterminacionSeleccionada === null) {
            $this->dispatch('vl-swal-error', mensaje: 'Seleccione una determinación primero.');

            return;
        }

        $this->busquedaItem = '';
        $this->modalAgregarAbierto = true;
    }

    public function cerrarModalAgregar(): void
    {
        $this->modalAgregarAbierto = false;
        $this->busquedaItem = '';
    }

    public function agregarItem(int $idItems): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'det-grupo-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $idDeterminacion = $this->idDeterminacionSeleccionada;
        if ($idDeterminacion === null) {
            return;
        }

        Itemsinforme::query()->findOrFail($idItems);

        $yaExiste = Renglonesxdeterminacion::query()
            ->where('idTipodeterminaciones', $idDeterminacion)
            ->where('idItemsinforme', $idItems)
            ->exists();

        if ($yaExiste) {
            $this->dispatch('vl-swal-error', mensaje: 'Ese ítem ya está en la plantilla de esta determinación.');

            return;
        }

        $maxOrden = (int) Renglonesxdeterminacion::query()
            ->where('idTipodeterminaciones', $idDeterminacion)
            ->max('orden');

        Renglonesxdeterminacion::query()->create([
            'idTipodeterminaciones' => $idDeterminacion,
            'idItemsinforme' => $idItems,
            'orden' => $maxOrden + 1,
        ]);

        $this->sincronizarFilasDesdeBd();
        $this->dispatch('vl-swal-exito', mensaje: 'Ítem agregado a la plantilla.');
    }

    public function guardarRenglon(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'det-grupo-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $fila = $this->filasRenglon[$id] ?? null;
        if ($fila === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el renglón a guardar.');

            return;
        }

        $validated = validator($fila, [
            'orden' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'orden.required' => 'El orden es obligatorio.',
            'orden.integer' => 'El orden debe ser un número entero.',
        ])->validate();

        $registro = $this->renglonDeDeterminacionSeleccionada($id);
        $registro->update(['orden' => (int) $validated['orden']]);
        $this->filasRenglon[$id] = $this->filaDesdeModelo($registro->fresh(['itemsinforme.grupo']));

        $this->dispatch('vl-swal-exito', mensaje: 'Renglón guardado correctamente.');
    }

    public function descartarRenglon(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $registro = $this->renglonDeDeterminacionSeleccionada($id);
        $this->filasRenglon[$id] = $this->filaDesdeModelo($registro->fresh(['itemsinforme.grupo']));
    }

    public function eliminarRenglon(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'det-grupo-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        $this->renglonDeDeterminacionSeleccionada($id)->delete();
        unset($this->filasRenglon[$id]);

        $this->dispatch('vl-swal-exito', mensaje: 'Renglón eliminado de la plantilla.');
    }

    public function moverRenglon(int $id, string $direccion): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'det-grupo-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $idDeterminacion = $this->idDeterminacionSeleccionada;
        if ($idDeterminacion === null) {
            return;
        }

        $ordenados = Renglonesxdeterminacion::query()
            ->where('idTipodeterminaciones', $idDeterminacion)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $indice = $ordenados->search(fn (Renglonesxdeterminacion $r) => (int) $r->id === $id);
        if ($indice === false) {
            return;
        }

        $indiceVecino = $direccion === 'arriba' ? $indice - 1 : $indice + 1;
        if (! isset($ordenados[$indiceVecino])) {
            return;
        }

        /** @var Renglonesxdeterminacion $actual */
        $actual = $ordenados[$indice];
        /** @var Renglonesxdeterminacion $vecino */
        $vecino = $ordenados[$indiceVecino];

        DB::transaction(function () use ($actual, $vecino): void {
            $ordenActual = (int) $actual->orden;
            $ordenVecino = (int) $vecino->orden;

            $actual->update(['orden' => $ordenVecino]);
            $vecino->update(['orden' => $ordenActual]);
        });

        $this->sincronizarFilasDesdeBd();
    }

    public function render()
    {
        $term = trim(mb_strtolower($this->busquedaDeterminacion));

        $query = Tipodeterminacion::query()
            ->withCount('renglonesPlantilla')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(nombre) LIKE ?', ["%{$term}%"])
                        ->orWhere('orden', 'like', "%{$term}%");
                });
            });

        if ($this->ordenListadoDeterminacion === 'nombre') {
            $query->orderByRaw('LOWER(nombre)')->orderBy('orden');
        } else {
            $query->orderBy('orden')->orderByRaw('LOWER(nombre)');
        }

        $determinaciones = $query->get();

        $determinacionActiva = $this->idDeterminacionSeleccionada !== null
            ? Tipodeterminacion::query()->find($this->idDeterminacionSeleccionada)
            : null;

        $idsRenglonesVisibles = $this->idsRenglonesOrdenados();

        return view('livewire.abm.det-por-grupo.det-por-grupo-index', [
            'determinaciones' => $determinaciones,
            'determinacionActiva' => $determinacionActiva,
            'idsRenglonesVisibles' => $idsRenglonesVisibles,
            'itemsDisponibles' => $this->itemsDisponiblesParaAgregar(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    /** @return Collection<int, Itemsinforme> */
    private function itemsDisponiblesParaAgregar(): Collection
    {
        if ($this->idDeterminacionSeleccionada === null) {
            return collect();
        }

        $idsEnPlantilla = Renglonesxdeterminacion::query()
            ->where('idTipodeterminaciones', $this->idDeterminacionSeleccionada)
            ->pluck('idItemsinforme');

        $term = trim(mb_strtolower($this->busquedaItem));

        return Itemsinforme::query()
            ->with('grupo')
            ->leftJoin('grupos', 'itemsinforme.idGrupos', '=', 'grupos.idGrupos')
            ->when(Schema::hasTable('renglonesxdeterminacion'), function ($query) {
                $query->leftJoinSub(
                    ItemsinformeCatalog::subconsultaOrdenPlantilla($this->idDeterminacionSeleccionada),
                    'rxd_orden',
                    function ($join) {
                        $join->on('itemsinforme.idItems', '=', 'rxd_orden.idItemsinforme');
                    }
                );
            })
            ->when($idsEnPlantilla->isNotEmpty(), fn ($q) => $q->whereNotIn('itemsinforme.idItems', $idsEnPlantilla))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(itemsinforme.nombreItem) LIKE ?', ["%{$term}%"])
                        ->orWhereHas('grupo', fn ($g) => $g->whereRaw('LOWER(nombreGrupo) LIKE ?', ["%{$term}%"]));
                });
            })
            ->orderByRaw('itemsinforme.idGrupos IS NULL')
            ->orderBy('itemsinforme.idGrupos')
            ->orderByRaw('rxd_orden.orden_plantilla IS NULL')
            ->orderBy('rxd_orden.orden_plantilla')
            ->orderBy('itemsinforme.nombreItem')
            ->limit(80)
            ->get();
    }

    private function sincronizarFilasDesdeBd(): void
    {
        if ($this->idDeterminacionSeleccionada === null) {
            $this->filasRenglon = [];

            return;
        }

        $this->filasRenglon = Renglonesxdeterminacion::query()
            ->with(['itemsinforme.grupo'])
            ->where('idTipodeterminaciones', $this->idDeterminacionSeleccionada)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Renglonesxdeterminacion $registro) => [
                (int) $registro->id => $this->filaDesdeModelo($registro),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function filaDesdeModelo(Renglonesxdeterminacion $registro): array
    {
        $item = $registro->itemsinforme;

        return [
            'orden' => (string) ($registro->orden ?? 0),
            'id_items' => (int) $registro->idItemsinforme,
            'nombre_item' => (string) ($item?->nombreItem ?? '—'),
            'nombre_grupo' => (string) ($item?->grupo?->nombreGrupo ?? '—'),
            'unidad' => (string) ($item?->unidadMedida ?? ''),
        ];
    }

    private function renglonDeDeterminacionSeleccionada(int $id): Renglonesxdeterminacion
    {
        $idDeterminacion = $this->idDeterminacionSeleccionada;
        abort_if($idDeterminacion === null, 404);

        return Renglonesxdeterminacion::query()
            ->whereKey($id)
            ->where('idTipodeterminaciones', $idDeterminacion)
            ->firstOrFail();
    }

    /** @return list<int> */
    private function idsRenglonesOrdenados(): array
    {
        $filas = $this->filasRenglon;

        uasort($filas, function (array $a, array $b): int {
            $cmp = (int) $a['orden'] <=> (int) $b['orden'];
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) $a['nombre_item'], (string) $b['nombre_item']);
        });

        return array_values(array_map('intval', array_keys($filas)));
    }
}
