<?php

namespace App\Livewire\Listados;

use App\Models\Cliente;
use App\Models\Especie;
use App\Models\Grupo;
use App\Models\Itemsinforme;
use App\Support\Listados\HistorialDeterminacionesConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class HistorialDeterminaciones extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public ?int $idClientes = null;

    public string $paciente = '';

    public ?int $idEspecies = null;

    public string $protocolo = '';

    public ?int $idGrupos = null;

    /** @var list<int|string> */
    public array $idsItems = [];

    public string $valorOperador = '';

    public string $valor = '';

    public string $valorHasta = '';

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        abort_unless(Schema::hasTable('renglones'), 404, 'La tabla de renglones no está disponible.');

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

    public function updatingIdEspecies(): void
    {
        $this->resetPage();
    }

    public function updatedIdEspecies(mixed $value): void
    {
        $this->idEspecies = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function updatingProtocolo(): void
    {
        $this->resetPage();
    }

    public function updatedIdGrupos(mixed $value): void
    {
        $this->idGrupos = ($value === '' || $value === null) ? null : (int) $value;
        $this->idsItems = [];
        $this->resetPage();
    }

    public function updatingIdsItems(): void
    {
        $this->resetPage();
    }

    public function updatedIdsItems(mixed $value): void
    {
        $this->idsItems = array_values(array_filter(
            array_map('intval', (array) $value),
            static fn (int $id): bool => $id > 0,
        ));
    }

    public function updatingValorOperador(): void
    {
        $this->resetPage();
    }

    public function updatedValorOperador(mixed $value): void
    {
        $this->valorOperador = (string) ($value ?? '');
        if ($this->valorOperador !== 'entre') {
            $this->valorHasta = '';
        }
    }

    public function updatingValor(): void
    {
        $this->resetPage();
    }

    public function updatingValorHasta(): void
    {
        $this->resetPage();
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $ctx = labCtx();
        $this->idClientes = ($ctx->esCliente() && $ctx->idClientes) ? $ctx->idClientes : null;
        $this->paciente = '';
        $this->idEspecies = null;
        $this->protocolo = '';
        $this->idGrupos = null;
        $this->idsItems = [];
        $this->valorOperador = '';
        $this->valor = '';
        $this->valorHasta = '';
        $this->fechaDesde = now()->startOfMonth()->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function filtrosActuales(): array
    {
        return [
            'idClientes' => $this->idClientes,
            'paciente' => trim($this->paciente),
            'idEspecies' => $this->idEspecies,
            'protocolo' => trim($this->protocolo),
            'idGrupos' => $this->idGrupos,
            'idsItems' => $this->idsItems,
            'valorOperador' => $this->valorOperador,
            'valor' => trim($this->valor),
            'valorHasta' => trim($this->valorHasta),
            'fechaDesde' => trim($this->fechaDesde),
            'fechaHasta' => trim($this->fechaHasta),
        ];
    }

    /** @return array<string, mixed> */
    private function queryParamsExport(): array
    {
        $params = [
            'idClientes' => $this->idClientes,
            'paciente' => trim($this->paciente) !== '' ? trim($this->paciente) : null,
            'idEspecies' => $this->idEspecies,
            'protocolo' => trim($this->protocolo) !== '' ? trim($this->protocolo) : null,
            'idGrupos' => $this->idGrupos,
            'valorOperador' => $this->valorOperador !== '' ? $this->valorOperador : null,
            'valor' => trim($this->valor) !== '' ? trim($this->valor) : null,
            'valorHasta' => trim($this->valorHasta) !== '' ? trim($this->valorHasta) : null,
            'fechaDesde' => trim($this->fechaDesde) !== '' ? trim($this->fechaDesde) : null,
            'fechaHasta' => trim($this->fechaHasta) !== '' ? trim($this->fechaHasta) : null,
        ];

        foreach ($this->idsItems as $id) {
            $params['idsItems'][] = (int) $id;
        }

        return array_filter($params, static function ($v) {
            if (is_array($v)) {
                return $v !== [];
            }

            return $v !== null && $v !== '';
        });
    }

    public function getPdfUrlProperty(): string
    {
        return route('listados.historial-determinaciones.pdf', $this->queryParamsExport());
    }

    public function getExcelUrlProperty(): string
    {
        return route('listados.historial-determinaciones.excel', $this->queryParamsExport());
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

        $grupos = Grupo::query()
            ->orderBy('orden')
            ->orderBy('nombreGrupo')
            ->get(['idGrupos', 'nombreGrupo']);

        $determinaciones = Itemsinforme::query()
            ->when($this->idGrupos, fn ($q) => $q->where('idGrupos', $this->idGrupos))
            ->whereIn('tipoItem', [1, 4, 7, 8, 9])
            ->orderBy('nombreItem')
            ->get(['idItems', 'nombreItem', 'idGrupos']);

        $registros = HistorialDeterminacionesConsulta::paginado(
            $this->filtrosActuales(),
            self::POR_PAGINA,
        );

        return view('livewire.listados.historial-determinaciones', [
            'clientes' => $clientes,
            'especies' => $especies,
            'grupos' => $grupos,
            'determinaciones' => $determinaciones,
            'registros' => $registros,
            'clienteBloqueado' => $clienteBloqueado,
            'periodoTexto' => HistorialDeterminacionesConsulta::etiquetaPeriodo(
                $this->fechaDesde,
                $this->fechaHasta,
            ),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
