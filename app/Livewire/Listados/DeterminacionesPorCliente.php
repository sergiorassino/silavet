<?php

namespace App\Livewire\Listados;

use App\Models\Cliente;
use App\Support\Listados\DeterminacionesPorClienteConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class DeterminacionesPorCliente extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public ?int $idClientes = null;

    public string $busqueda = '';

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    /** @var list<int> */
    public array $expandidos = [];

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        abort_unless(Schema::hasTable('determinaciones'), 404, 'La tabla de determinaciones no está disponible.');

        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes) {
            $this->idClientes = $ctx->idClientes;
        }

        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->toDateString();
    }

    public function hydrate(): void
    {
        $this->expandidos = array_values(array_unique(array_map('intval', $this->expandidos)));
    }

    public function updatingIdClientes(): void
    {
        $this->expandidos = [];
        $this->resetPage();
    }

    public function updatedIdClientes(mixed $value): void
    {
        $this->idClientes = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function updatingBusqueda(): void
    {
        $this->expandidos = [];
        $this->resetPage();
    }

    public function updatingFechaDesde(): void
    {
        $this->expandidos = [];
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->expandidos = [];
        $this->resetPage();
    }

    public function toggleCliente(int $idClientes): void
    {
        if (in_array($idClientes, $this->expandidos, true)) {
            $this->expandidos = array_values(array_filter(
                $this->expandidos,
                static fn (int $id): bool => $id !== $idClientes,
            ));

            return;
        }

        $this->expandidos[] = $idClientes;
    }

    public function limpiarFiltros(): void
    {
        $ctx = labCtx();
        $this->idClientes = ($ctx->esCliente() && $ctx->idClientes) ? $ctx->idClientes : null;
        $this->busqueda = '';
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->expandidos = [];
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function filtrosActuales(): array
    {
        return [
            'idClientes' => $this->idClientes,
            'busqueda' => trim($this->busqueda),
            'fechaDesde' => trim($this->fechaDesde),
            'fechaHasta' => trim($this->fechaHasta),
        ];
    }

    /** @return array<string, mixed> */
    private function queryParamsExport(): array
    {
        return array_filter([
            'idClientes' => $this->idClientes,
            'busqueda' => trim($this->busqueda) !== '' ? trim($this->busqueda) : null,
            'fechaDesde' => trim($this->fechaDesde) !== '' ? trim($this->fechaDesde) : null,
            'fechaHasta' => trim($this->fechaHasta) !== '' ? trim($this->fechaHasta) : null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    public function getExcelUrlProperty(): string
    {
        return route('listados.determinaciones-por-cliente.excel', $this->queryParamsExport());
    }

    public function render()
    {
        $ctx = labCtx();
        $clienteBloqueado = $ctx->esCliente() && (bool) $ctx->idClientes;

        $clientes = Cliente::query()
            ->when($clienteBloqueado, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        $grupos = DeterminacionesPorClienteConsulta::gruposPaginados(
            $this->filtrosActuales(),
            self::POR_PAGINA,
            (int) $this->getPage(),
        );

        $idsEnPagina = collect($grupos->items())->map(fn (object $g) => (int) $g->idClientes)->all();
        $idsExpandidos = array_values(array_intersect($this->expandidos, $idsEnPagina));
        $detalles = DeterminacionesPorClienteConsulta::filasPorClientes(
            $idsExpandidos,
            $this->filtrosActuales(),
        );

        $resumenPagina = [
            'cantidad_grupos' => count($grupos->items()),
            'cantidad' => (int) collect($grupos->items())->sum('cantidad'),
            'total_precio' => round((float) collect($grupos->items())->sum('sumaPrecio'), 2),
        ];

        return view('livewire.listados.determinaciones-por-cliente', [
            'clientes' => $clientes,
            'grupos' => $grupos,
            'detalles' => $detalles,
            'resumenPagina' => $resumenPagina,
            'clienteBloqueado' => $clienteBloqueado,
            'periodoTexto' => DeterminacionesPorClienteConsulta::etiquetaPeriodo(
                $this->fechaDesde,
                $this->fechaHasta,
            ),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
