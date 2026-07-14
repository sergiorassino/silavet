<?php

namespace App\Livewire\Listados;

use App\Models\Cliente;
use App\Models\Tipodeterminacion;
use App\Support\Listados\CantidadDeterminacionesComparacConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class CantidadDeterminacionesComparac extends Component
{
    public string $paso = 'filtros';

    public ?int $idClientes = null;

    /** @var list<int> */
    public array $idsSeleccionados = [];

    /** @var list<int> */
    public array $marcadosDisponibles = [];

    /** @var list<int> */
    public array $marcadosSeleccionados = [];

    public string $periodo1Desde = '';

    public string $periodo1Hasta = '';

    public string $periodo2Desde = '';

    public string $periodo2Hasta = '';

    public string $orden = 'nombre';

    public string $tipoGrafico = 'bar';

    public bool $mostrarResumen = true;

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        abort_unless(Schema::hasTable('determinaciones'), 404, 'La tabla de determinaciones no está disponible.');
        abort_unless(Schema::hasTable('tipodeterminaciones'), 404, 'La tabla de tipodeterminaciones no está disponible.');

        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes) {
            $this->idClientes = $ctx->idClientes;
        }

        $anioActual = (int) now()->year;
        $this->periodo1Desde = ($anioActual - 1).'-01-01';
        $this->periodo1Hasta = ($anioActual - 1).'-12-31';
        $this->periodo2Desde = $anioActual.'-01-01';
        $this->periodo2Hasta = now()->toDateString();
    }

    public function updatedIdClientes(mixed $value): void
    {
        $this->idClientes = ($value === '' || $value === null) ? null : (int) $value;
        if ($this->idClientes !== null) {
            $this->assertClienteEnAlcance($this->idClientes);
        }
    }

    public function moverDerecha(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        foreach ($this->marcadosDisponibles as $id) {
            $id = (int) $id;
            if ($id > 0 && ! in_array($id, $this->idsSeleccionados, true)) {
                $this->idsSeleccionados[] = $id;
            }
        }

        $this->marcadosDisponibles = [];
        $this->idsSeleccionados = array_values($this->idsSeleccionados);
    }

    public function moverTodasDerecha(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $idsYa = $this->idsSeleccionados;
        $idsTodos = Tipodeterminacion::query()
            ->orderBy('nombre')
            ->pluck('idTipodeterminaciones')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($idsTodos as $id) {
            if (! in_array($id, $idsYa, true)) {
                $this->idsSeleccionados[] = $id;
            }
        }

        $this->marcadosDisponibles = [];
        $this->idsSeleccionados = array_values($this->idsSeleccionados);
    }

    public function moverIzquierda(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $quitar = array_map('intval', $this->marcadosSeleccionados);
        $this->idsSeleccionados = array_values(array_filter(
            $this->idsSeleccionados,
            static fn (int $id): bool => ! in_array($id, $quitar, true),
        ));
        $this->marcadosSeleccionados = [];
    }

    public function moverTodasIzquierda(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        $this->idsSeleccionados = [];
        $this->marcadosSeleccionados = [];
    }

    public function generar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $this->validate(
            [
                'idsSeleccionados' => ['required', 'array', 'min:1'],
                'idsSeleccionados.*' => ['integer'],
                'periodo1Desde' => ['required', 'date'],
                'periodo1Hasta' => ['required', 'date', 'after_or_equal:periodo1Desde'],
                'periodo2Desde' => ['required', 'date'],
                'periodo2Hasta' => ['required', 'date', 'after_or_equal:periodo2Desde'],
                'idClientes' => ['nullable', 'integer'],
            ],
            [
                'idsSeleccionados.required' => 'Seleccione al menos una determinación.',
                'idsSeleccionados.min' => 'Seleccione al menos una determinación.',
                'periodo1Desde.required' => 'Indique el inicio del período 1.',
                'periodo1Hasta.required' => 'Indique el fin del período 1.',
                'periodo1Hasta.after_or_equal' => 'El fin del período 1 debe ser posterior o igual al inicio.',
                'periodo2Desde.required' => 'Indique el inicio del período 2.',
                'periodo2Hasta.required' => 'Indique el fin del período 2.',
                'periodo2Hasta.after_or_equal' => 'El fin del período 2 debe ser posterior o igual al inicio.',
            ],
        );

        if ($this->idClientes !== null) {
            $this->assertClienteEnAlcance($this->idClientes);
        }

        $this->paso = 'resultado';
    }

    public function volverFiltros(): void
    {
        $this->paso = 'filtros';
    }

    public function updatedOrden(mixed $value): void
    {
        $orden = (string) $value;
        $this->orden = in_array($orden, CantidadDeterminacionesComparacConsulta::ORDENES, true)
            ? $orden
            : 'nombre';
    }

    public function updatedTipoGrafico(mixed $value): void
    {
        $tipo = (string) $value;
        $permitidos = ['bar', 'line', 'area', 'pie', 'stacked', 'horizontalBar'];
        $this->tipoGrafico = in_array($tipo, $permitidos, true) ? $tipo : 'bar';
    }

    /** @return array<string, mixed> */
    public function filtrosActuales(): array
    {
        return [
            'idClientes' => $this->idClientes,
            'idsTipodeterminaciones' => array_values(array_map('intval', $this->idsSeleccionados)),
            'periodo1Desde' => trim($this->periodo1Desde),
            'periodo1Hasta' => trim($this->periodo1Hasta),
            'periodo2Desde' => trim($this->periodo2Desde),
            'periodo2Hasta' => trim($this->periodo2Hasta),
            'orden' => $this->orden,
        ];
    }

    /** @return array<string, mixed> */
    public function queryExport(): array
    {
        $f = $this->filtrosActuales();

        return array_filter([
            'idClientes' => $f['idClientes'],
            'ids' => $f['idsTipodeterminaciones'],
            'periodo1Desde' => $f['periodo1Desde'] !== '' ? $f['periodo1Desde'] : null,
            'periodo1Hasta' => $f['periodo1Hasta'] !== '' ? $f['periodo1Hasta'] : null,
            'periodo2Desde' => $f['periodo2Desde'] !== '' ? $f['periodo2Desde'] : null,
            'periodo2Hasta' => $f['periodo2Hasta'] !== '' ? $f['periodo2Hasta'] : null,
            'orden' => $f['orden'] !== 'nombre' ? $f['orden'] : null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    public function getPdfUrlProperty(): string
    {
        return route('listados.cantidad-determinaciones-comparac.pdf', $this->queryExport());
    }

    public function getExcelUrlProperty(): string
    {
        return route('listados.cantidad-determinaciones-comparac.excel', $this->queryExport());
    }

    public function getChartPdfUrlProperty(): string
    {
        return route('listados.cantidad-determinaciones-comparac.chart-pdf');
    }

    public function render()
    {
        $ctx = labCtx();
        $clienteBloqueado = $ctx->esCliente() && (bool) $ctx->idClientes;

        $clientes = Cliente::query()
            ->when($clienteBloqueado, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        $disponibles = Tipodeterminacion::query()
            ->when($this->idsSeleccionados !== [], fn ($q) => $q->whereNotIn('idTipodeterminaciones', $this->idsSeleccionados))
            ->orderBy('nombre')
            ->get(['idTipodeterminaciones', 'nombre']);

        $seleccionados = Tipodeterminacion::query()
            ->whereIn('idTipodeterminaciones', $this->idsSeleccionados ?: [0])
            ->get(['idTipodeterminaciones', 'nombre'])
            ->sortBy(fn ($t) => array_search((int) $t->idTipodeterminaciones, $this->idsSeleccionados, true))
            ->values();

        $filas = collect();
        $resumen = null;
        $payloadGrafico = null;

        if ($this->paso === 'resultado') {
            $filtros = $this->filtrosActuales();
            $filas = CantidadDeterminacionesComparacConsulta::comparativa($filtros);
            $resumen = CantidadDeterminacionesComparacConsulta::resumen($filas, $filtros);
            $payloadGrafico = [
                'labels' => $filas->pluck('nombre')->all(),
                'periodo1' => $filas->pluck('cantidad1')->all(),
                'periodo2' => $filas->pluck('cantidad2')->all(),
                'label1' => $resumen['periodo1Corto'],
                'label2' => $resumen['periodo2Corto'],
                'tipo' => $this->tipoGrafico,
            ];
        }

        return view('livewire.listados.cantidad-determinaciones-comparac', [
            'clientes' => $clientes,
            'disponibles' => $disponibles,
            'seleccionados' => $seleccionados,
            'clienteBloqueado' => $clienteBloqueado,
            'filas' => $filas,
            'resumen' => $resumen,
            'payloadGrafico' => $payloadGrafico,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function assertClienteEnAlcance(int $idClientes): void
    {
        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes && (int) $ctx->idClientes !== $idClientes) {
            abort(403);
        }

        $existe = Cliente::query()->where('idClientes', $idClientes)->exists();
        abort_unless($existe, 404);
    }
}
