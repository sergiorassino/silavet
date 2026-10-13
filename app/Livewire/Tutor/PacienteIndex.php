<?php

namespace App\Livewire\Tutor;

use App\Support\Security\OpaqueRouteToken;
use App\Support\Tutor\TutorContext;
use App\Support\Tutor\TutorPacientesConsulta;
use Livewire\Component;
use Livewire\WithPagination;

class PacienteIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $ctx = TutorContext::fromSession();
        abort_unless($ctx->isValid(), 403);

        $pacientes = TutorPacientesConsulta::listado(
            $ctx->dni,
            $this->busqueda,
            $this->fechaDesde,
            $this->fechaHasta,
        )->paginate(self::POR_PAGINA);

        return view('livewire.tutor.paciente-index', [
            'pacientes' => $pacientes,
            'nombreTutor' => $ctx->nombre,
        ])->layout('layouts.staff', TutorContext::layoutParams());
    }

    public function urlInforme(int $idPacientes): string
    {
        $ctx = TutorContext::fromSession();
        abort_unless($ctx->isValid(), 403);

        return route('tutor.pacientes.informe', [
            'ref' => OpaqueRouteToken::forInformeTutor($idPacientes, $ctx->dni),
        ]);
    }
}
