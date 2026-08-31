<?php

namespace App\Livewire\Cliente;

use App\Models\Paciente;
use App\Support\Cliente\DetalleDeterminacionesConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\Protocolos\PacienteListadoFiltros;
use App\Support\Security\OpaqueRouteToken;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class PacienteDeterminacionesDetalle extends Component
{
    public string $ref = '';

    public string $busquedaRapida = '';

    /** @var array{vista?: string, filtroEstado?: string, fechaVista?: string, page?: int} */
    public array $listadoFiltros = [];

    public function mount(string $ref): void
    {
        abort_unless(labCtx()->esCliente() && labCtx()->idClientes, 403);
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);
        abort_unless(Schema::hasTable('determinaciones'), 404, 'La tabla de determinaciones no está disponible.');

        $decoded = OpaqueRouteToken::decodePacienteDeterminacionesCliente($ref);
        abort_if($decoded === null, 404);

        $uid = (int) (auth()->id() ?? 0);
        abort_if($decoded['u'] !== $uid, 404);

        $paciente = DetalleDeterminacionesConsulta::pacienteEnAlcance($decoded['id']);
        abort_if($paciente === null, 404);

        $this->ref = $ref;
        $this->listadoFiltros = PacienteListadoFiltros::desdeRequest();
    }

    public function render()
    {
        $paciente = $this->pacienteDesdeRef();
        $detalle = DetalleDeterminacionesConsulta::armar($paciente, $this->busquedaRapida);

        return view('livewire.cliente.paciente-determinaciones-detalle', [
            'detalle' => $detalle,
            'urlVolver' => PacienteListadoFiltros::urlIndexCliente($this->listadoFiltros, (int) $paciente->idPacientes),
            'urlPdf' => route('cliente.pacientes.determinaciones.pdf', ['ref' => $this->ref]),
        ])->layout('layouts.staff', UsuarioMenuPortal::clienteLayoutParams());
    }

    private function pacienteDesdeRef(): Paciente
    {
        $decoded = OpaqueRouteToken::decodePacienteDeterminacionesCliente($this->ref);
        abort_if($decoded === null, 404);
        abort_if($decoded['u'] !== (int) (auth()->id() ?? 0), 404);

        $paciente = DetalleDeterminacionesConsulta::pacienteEnAlcance($decoded['id']);
        abort_if($paciente === null, 404);

        return $paciente;
    }
}
