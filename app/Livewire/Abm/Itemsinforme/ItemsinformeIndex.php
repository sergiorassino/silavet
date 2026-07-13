<?php

namespace App\Livewire\Abm\Itemsinforme;

use App\Models\Grupo;
use App\Models\Itemsinforme;
use App\Support\Itemsinforme\ItemsinformeCatalog;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ItemsinformeIndex extends Component
{
    public string $busqueda = '';

    public string $filtroGrupo = '';

    /** @var array<int, array<string, mixed>> */
    public array $filas = [];

    public bool $modalCampoAbierto = false;

    public ?int $editandoId = null;

    public string $campoEditando = '';

    public string $valorCampo = '';

    public ?int $idItemNuevo = null;

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);
        $this->sincronizarFilasDesdeBd();
    }

    public function updatedBusqueda(): void
    {
        //
    }

    public function updatedFiltroGrupo(): void
    {
        //
    }

    public function agregarItem(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'itemsinf-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        if (! Grupo::query()->exists()) {
            $this->dispatch('vl-swal-error', mensaje: 'Debe existir al menos un grupo para crear ítems.');

            return;
        }

        $registro = Itemsinforme::query()->create([
            'idGrupos' => null,
            'nombreItem' => '',
            'tipoItem' => null,
            'estiloNum' => null,
            'textos' => null,
            'letra' => 7,
            'negrita' => 0,
            'unidadMedida' => null,
            'unidadMedida2' => null,
            'refCaninos' => null,
            'refFelinos' => null,
            'refEquinos' => null,
            'refBovinos' => null,
            'refPorcinos' => null,
            'refOvinos' => null,
            'refComun' => null,
            'actualiza' => null,
            'idAnalizador' => null,
        ]);

        $id = (int) $registro->idItems;
        $this->idItemNuevo = $id;
        $this->filas[$id] = $this->filaDesdeModelo($registro->fresh(['grupo']));
        $this->abrirEdicionCampo($id, 'nombre_item');
    }

    public function abrirEdicionCampo(int $id, string $campo): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $config = ItemsinformeCatalog::camposEditables()[$campo] ?? null;
        if ($config === null || ! isset($this->filas[$id])) {
            return;
        }

        $this->editandoId = $id;
        $this->campoEditando = $campo;
        $this->valorCampo = (string) ($this->filas[$id][$campo] ?? '');
        if ($campo === 'estilo_num' && $this->valorCampo !== '') {
            $this->valorCampo = ItemsinformeCatalog::formatoValorParaEdicion((int) $this->valorCampo);
        }
        $this->resetValidation();
        $this->modalCampoAbierto = true;
    }

    public function cerrarEdicionCampo(): void
    {
        $this->modalCampoAbierto = false;
        $this->editandoId = null;
        $this->campoEditando = '';
        $this->valorCampo = '';
        $this->resetValidation();
    }

    public function guardarCampo(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $config = ItemsinformeCatalog::camposEditables()[$this->campoEditando] ?? null;
        if ($config === null || $this->editandoId === null) {
            return;
        }

        $key = 'itemsinf-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        if ($this->valorCampo === '' && in_array($this->campoEditando, ['id_grupos', 'tipo_item', 'estilo_num', 'actualiza'], true)) {
            $valor = null;
        } else {
            $validated = $this->validate(
                ['valorCampo' => $this->reglasParaCampo($this->campoEditando)],
                $this->mensajesParaCampo($this->campoEditando),
            );
            $valor = $this->normalizarValorCampo($this->campoEditando, $validated['valorCampo']);
        }

        $registro = Itemsinforme::query()->findOrFail($this->editandoId);
        $registro->update([
            $config['columna'] => $valor,
        ]);

        $idGuardado = $this->editandoId;
        $enfocarTrasNombre = $this->campoEditando === 'nombre_item'
            && $this->idItemNuevo !== null
            && $this->idItemNuevo === $idGuardado;

        $this->filas[$idGuardado] = $this->filaDesdeModelo(
            $registro->fresh(['grupo']),
            $this->filas[$idGuardado] ?? null,
        );
        $this->cerrarEdicionCampo();

        if ($enfocarTrasNombre) {
            $this->idItemNuevo = null;
            $this->dispatch('itemsinf-enfocar-fila', id: $idGuardado);
        }
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'itemsinf-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Itemsinforme::query()->findOrFail($id);

        if ($this->itemEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: el ítem está asociado a plantillas o resultados.',
                titulo: 'Ítem en uso'
            );

            return;
        }

        Itemsinforme::query()->whereKey($id)->delete();
        unset($this->filas[$id]);

        if ($this->editandoId === $id) {
            $this->cerrarEdicionCampo();
        }

        $this->dispatch('vl-swal-exito', mensaje: 'Ítem eliminado correctamente.');
    }

    public function render()
    {
        $term = trim(mb_strtolower($this->busqueda));
        $filtroGrupo = $this->filtroGrupo !== '' ? (int) $this->filtroGrupo : null;

        $idsVisibles = collect($this->filas)
            ->filter(function (array $fila) use ($term, $filtroGrupo) {
                if ($filtroGrupo !== null) {
                    if ($fila['id_grupos'] === '' || (int) $fila['id_grupos'] !== $filtroGrupo) {
                        return false;
                    }
                }

                if ($term === '') {
                    return true;
                }

                $nombre = mb_strtolower((string) $fila['nombre_item']);
                $grupo = mb_strtolower((string) $fila['nombre_grupo']);
                $analizador = mb_strtolower((string) $fila['id_analizador']);

                return str_contains($nombre, $term)
                    || str_contains($grupo, $term)
                    || str_contains((string) $fila['id_items'], $term)
                    || str_contains($analizador, $term);
            })
            ->sortBy(fn (array $fila) => $fila['orden_plantilla'] === '' ? PHP_INT_MAX : (int) $fila['orden_plantilla'])
            ->sortBy(fn (array $fila) => $fila['orden_grupo'] === '' ? PHP_INT_MAX : (int) $fila['orden_grupo'])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $campoActual = $this->campoEditando !== ''
            ? (ItemsinformeCatalog::camposEditables()[$this->campoEditando] ?? null)
            : null;

        return view('livewire.abm.itemsinforme.itemsinforme-index', [
            'idsVisibles' => $idsVisibles,
            'grupos' => Grupo::query()->orderBy('orden')->orderBy('nombreGrupo')->get(),
            'modosCarga' => ItemsinformeCatalog::modosCarga(),
            'formatosValor' => ItemsinformeCatalog::formatosValorSelect(),
            'opcionesSiNo' => ItemsinformeCatalog::siNo(),
            'columnasVisibles' => ItemsinformeCatalog::columnasVisibles(),
            'camposEditables' => ItemsinformeCatalog::camposEditables(),
            'campoActual' => $campoActual,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    /** @return list<string|object> */
    private function reglasParaCampo(string $campo): array
    {
        return match ($campo) {
            'nombre_item' => ['required', 'string', 'max:100'],
            'id_grupos' => ['nullable', 'integer', 'exists:grupos,idGrupos'],
            'tipo_item' => ['nullable', 'integer', 'in:'.implode(',', ItemsinformeCatalog::modosCargaValores())],
            'textos' => ['nullable', 'string', 'max:500'],
            'unidad_medida', 'unidad_medida2' => ['nullable', 'string', 'max:20'],
            'ref_caninos', 'ref_felinos', 'ref_equinos', 'ref_porcinos', 'ref_bovinos' => ['nullable', 'string', 'max:80'],
            'estilo_num' => ['nullable', 'integer', 'in:'.implode(',', array_keys(ItemsinformeCatalog::formatosValor()))],
            'actualiza' => ['nullable', 'integer', 'in:0,1'],
            'id_analizador' => ['nullable', 'string', 'max:20'],
            default => throw ValidationException::withMessages([
                'valorCampo' => 'Campo no editable.',
            ]),
        };
    }

    /** @return array<string, string> */
    private function mensajesParaCampo(string $campo): array
    {
        $label = ItemsinformeCatalog::camposEditables()[$campo]['label'] ?? 'Campo';

        return [
            'valorCampo.required' => "{$label} es obligatorio.",
            'valorCampo.max' => "{$label} supera la longitud permitida.",
            'valorCampo.in' => "{$label} no es válido.",
            'valorCampo.exists' => "{$label} no es válido.",
        ];
    }

    private function normalizarValorCampo(string $campo, mixed $valor): mixed
    {
        if (in_array($campo, ['id_grupos', 'tipo_item', 'estilo_num', 'actualiza'], true)) {
            if ($valor === null || $valor === '') {
                return null;
            }

            return (int) $valor;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function sincronizarFilasDesdeBd(): void
    {
        $query = Itemsinforme::query()
            ->with('grupo')
            ->leftJoin('grupos', 'itemsinforme.idGrupos', '=', 'grupos.idGrupos');

        if (Schema::hasTable('renglonesxdeterminacion')) {
            $query->leftJoinSub(
                ItemsinformeCatalog::subconsultaOrdenPlantilla(),
                'rxd_orden',
                function ($join) {
                    $join->on('itemsinforme.idItems', '=', 'rxd_orden.idItemsinforme');
                }
            )->addSelect('itemsinforme.*', 'grupos.orden as orden_grupo', 'rxd_orden.orden_plantilla');
        } else {
            $query->addSelect('itemsinforme.*', 'grupos.orden as orden_grupo')
                ->selectRaw('NULL as orden_plantilla');
        }

        $this->filas = $query
            ->orderByRaw('grupos.orden IS NULL')
            ->orderBy('grupos.orden')
            ->orderByRaw('orden_plantilla IS NULL')
            ->orderBy('orden_plantilla')
            ->get()
            ->mapWithKeys(fn (Itemsinforme $registro) => [
                (int) $registro->idItems => $this->filaDesdeModelo($registro),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function filaDesdeModelo(Itemsinforme $registro, ?array $filaAnterior = null): array
    {
        $ordenGrupo = $registro->orden_grupo ?? $registro->grupo?->orden;
        $ordenPlantilla = $registro->orden_plantilla
            ?? ($filaAnterior['orden_plantilla'] ?? null);

        return [
            'id_items' => (string) $registro->idItems,
            'id_grupos' => $registro->idGrupos !== null ? (string) $registro->idGrupos : '',
            'nombre_grupo' => $registro->idGrupos !== null ? (string) ($registro->grupo?->nombreGrupo ?? '') : '',
            'orden_grupo' => $ordenGrupo !== null ? (string) $ordenGrupo : '',
            'orden_plantilla' => $ordenPlantilla !== null && $ordenPlantilla !== '' ? (string) $ordenPlantilla : '',
            'nombre_item' => (string) $registro->nombreItem,
            'tipo_item' => $registro->tipoItem !== null ? (string) $registro->tipoItem : '',
            'textos' => (string) ($registro->textos ?? ''),
            'unidad_medida' => (string) ($registro->unidadMedida ?? ''),
            'unidad_medida2' => (string) ($registro->unidadMedida2 ?? ''),
            'ref_caninos' => (string) ($registro->refCaninos ?? ''),
            'ref_felinos' => (string) ($registro->refFelinos ?? ''),
            'ref_equinos' => (string) ($registro->refEquinos ?? ''),
            'ref_porcinos' => (string) ($registro->refPorcinos ?? ''),
            'ref_bovinos' => (string) ($registro->refBovinos ?? ''),
            'estilo_num' => $registro->estiloNum !== null ? (string) $registro->estiloNum : '',
            'actualiza' => $registro->actualiza !== null ? ((int) $registro->actualiza > 0 ? '1' : '0') : '',
            'id_analizador' => (string) ($registro->idAnalizador ?? ''),
        ];
    }

    private function itemEnUso(int $id): bool
    {
        if (Schema::hasTable('renglonesxdeterminacion')) {
            $enPlantilla = DB::table('renglonesxdeterminacion')
                ->where('idItemsinforme', $id)
                ->exists();

            if ($enPlantilla) {
                return true;
            }
        }

        if (Schema::hasTable('renglones')) {
            $columnaItem = Schema::hasColumn('renglones', 'idItemsinforme')
                ? 'idItemsinforme'
                : (Schema::hasColumn('renglones', 'idItems') ? 'idItems' : null);

            if ($columnaItem !== null) {
                return DB::table('renglones')
                    ->where($columnaItem, $id)
                    ->exists();
            }
        }

        return false;
    }
}
