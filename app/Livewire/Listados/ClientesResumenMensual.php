<?php

namespace App\Livewire\Listados;

use App\Models\Cliente;
use App\Support\Listados\ClientesResumenMensualConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class ClientesResumenMensual extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public ?int $idClientes = null;

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        abort_unless(Schema::hasTable('pacientes'), 404, 'La tabla de pacientes no está disponible.');

        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes) {
            $this->idClientes = $ctx->idClientes;
        }

        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->toDateString();
    }

    public function updatingIdClientes(): void
    {
        $this->resetPage();
    }

    public function updatedIdClientes(mixed $value): void
    {
        $this->idClientes = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }

    public function filtrar(): void
    {
        $n = ClientesResumenMensualConsulta::normalizarFiltros($this->filtrosActuales());
        $this->fechaDesde = $n['fechaDesde'];
        $this->fechaHasta = $n['fechaHasta'];
        $this->idClientes = $n['idClientes'];
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $ctx = labCtx();
        $this->idClientes = ($ctx->esCliente() && $ctx->idClientes) ? $ctx->idClientes : null;
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->resetPage();
    }

    /** @return array{idClientes: int|null, fechaDesde: string, fechaHasta: string} */
    public function filtrosActuales(): array
    {
        return [
            'idClientes' => $this->idClientes,
            'fechaDesde' => trim($this->fechaDesde),
            'fechaHasta' => trim($this->fechaHasta),
        ];
    }

    /** @return array<string, mixed> */
    private function queryParamsExport(): array
    {
        $n = ClientesResumenMensualConsulta::normalizarFiltros($this->filtrosActuales());

        return array_filter([
            'idClientes' => $n['idClientes'],
            'fechaDesde' => $n['fechaDesde'],
            'fechaHasta' => $n['fechaHasta'],
        ], static fn ($v) => $v !== null && $v !== '');
    }

    public function getPdfUrlProperty(): string
    {
        return route('listados.clientes-resumen-mensual.pdf', $this->queryParamsExport());
    }

    public function getExcelUrlProperty(): string
    {
        return route('listados.clientes-resumen-mensual.excel', $this->queryParamsExport());
    }

    public function render()
    {
        $ctx = labCtx();
        $clienteBloqueado = $ctx->esCliente() && (bool) $ctx->idClientes;
        $filtros = ClientesResumenMensualConsulta::normalizarFiltros($this->filtrosActuales());

        $clientes = Cliente::query()
            ->when($clienteBloqueado, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        $registros = ClientesResumenMensualConsulta::paginado($filtros, self::POR_PAGINA);
        $bloques = ClientesResumenMensualConsulta::bloquesAgrupados($registros->items());
        $totalesPagina = ClientesResumenMensualConsulta::acumular($registros->items());
        $totalesGeneral = ClientesResumenMensualConsulta::totales($filtros);
        $infoCliente = ClientesResumenMensualConsulta::infoClienteFiltro($filtros);

        return view('livewire.listados.clientes-resumen-mensual', [
            'clientes' => $clientes,
            'registros' => $registros,
            'bloques' => $bloques,
            'totalesPagina' => $totalesPagina,
            'totalesGeneral' => $totalesGeneral,
            'infoCliente' => $infoCliente,
            'clienteBloqueado' => $clienteBloqueado,
            'periodoTexto' => ClientesResumenMensualConsulta::etiquetaPeriodo(
                $filtros['fechaDesde'],
                $filtros['fechaHasta'],
            ),
            'mensajeVacio' => ClientesResumenMensualConsulta::mensajeVacio($filtros, $infoCliente),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
