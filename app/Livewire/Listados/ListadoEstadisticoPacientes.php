<?php

namespace App\Livewire\Listados;

use App\Models\Cliente;
use App\Models\Especie;
use App\Models\Raza;
use App\Support\Listados\ListadoEstadisticoPacientesConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class ListadoEstadisticoPacientes extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public ?int $idClientes = null;

    public string $paciente = '';

    public ?int $idEspecies = null;

    public ?int $idRazas = null;

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public bool $agruparPorCliente = false;

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

    public function updatingPaciente(): void
    {
        $this->resetPage();
    }

    public function updatedIdEspecies(mixed $value): void
    {
        $this->idEspecies = ($value === '' || $value === null) ? null : (int) $value;
        $this->idRazas = null;
        $this->resetPage();
    }

    public function updatingIdRazas(): void
    {
        $this->resetPage();
    }

    public function updatedIdRazas(mixed $value): void
    {
        $this->idRazas = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatingAgruparPorCliente(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $ctx = labCtx();
        $this->idClientes = ($ctx->esCliente() && $ctx->idClientes) ? $ctx->idClientes : null;
        $this->paciente = '';
        $this->idEspecies = null;
        $this->idRazas = null;
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->agruparPorCliente = false;
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function filtrosActuales(): array
    {
        return [
            'idClientes' => $this->idClientes,
            'paciente' => trim($this->paciente),
            'idEspecies' => $this->idEspecies,
            'idRazas' => $this->idRazas,
            'fechaDesde' => trim($this->fechaDesde),
            'fechaHasta' => trim($this->fechaHasta),
            'agruparPorCliente' => $this->agruparPorCliente,
        ];
    }

    public function getPdfUrlProperty(): string
    {
        return route('listados.estadistico-pacientes.pdf', array_filter([
            'idClientes' => $this->idClientes,
            'paciente' => trim($this->paciente) !== '' ? trim($this->paciente) : null,
            'idEspecies' => $this->idEspecies,
            'idRazas' => $this->idRazas,
            'fechaDesde' => trim($this->fechaDesde) !== '' ? trim($this->fechaDesde) : null,
            'fechaHasta' => trim($this->fechaHasta) !== '' ? trim($this->fechaHasta) : null,
            'agruparPorCliente' => $this->agruparPorCliente ? 1 : null,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function getExcelUrlProperty(): string
    {
        return route('listados.estadistico-pacientes.excel', array_filter([
            'idClientes' => $this->idClientes,
            'paciente' => trim($this->paciente) !== '' ? trim($this->paciente) : null,
            'idEspecies' => $this->idEspecies,
            'idRazas' => $this->idRazas,
            'fechaDesde' => trim($this->fechaDesde) !== '' ? trim($this->fechaDesde) : null,
            'fechaHasta' => trim($this->fechaHasta) !== '' ? trim($this->fechaHasta) : null,
            'agruparPorCliente' => $this->agruparPorCliente ? 1 : null,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function render()
    {
        $ctx = labCtx();
        $clienteBloqueado = $ctx->esCliente() && (bool) $ctx->idClientes;

        $clientes = Cliente::query()
            ->when($clienteBloqueado, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        $especies = Especie::query()->orderBy('nombre')->get(['idEspecies', 'nombre']);

        $razas = collect();
        if ($this->idEspecies) {
            $razas = Raza::query()
                ->where('idEspecies', $this->idEspecies)
                ->orderBy('nombre')
                ->get(['idRazas', 'nombre']);
        }

        $registros = ListadoEstadisticoPacientesConsulta::paginado(
            $this->filtrosActuales(),
            self::POR_PAGINA,
        );

        $resumenPagina = ListadoEstadisticoPacientesConsulta::resumen($registros->items());

        $bloques = $this->agruparPorCliente
            ? ListadoEstadisticoPacientesConsulta::bloquesAgrupados($registros->items())
            : null;

        return view('livewire.listados.listado-estadistico-pacientes', [
            'clientes' => $clientes,
            'especies' => $especies,
            'razas' => $razas,
            'registros' => $registros,
            'resumenPagina' => $resumenPagina,
            'bloques' => $bloques,
            'clienteBloqueado' => $clienteBloqueado,
            'periodoTexto' => ListadoEstadisticoPacientesConsulta::etiquetaPeriodo(
                $this->fechaDesde,
                $this->fechaHasta,
            ),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
