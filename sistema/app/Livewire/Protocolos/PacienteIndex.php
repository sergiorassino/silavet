<?php

namespace App\Livewire\Protocolos;

use App\Models\Paciente;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Livewire\Component;
use Livewire\WithPagination;

class PacienteIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public const VISTA_HOY = 'hoy';

    public const VISTA_HISTORIAL = 'historial';

    public string $busqueda = '';

    public string $vista = self::VISTA_HOY;

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingVista(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->busqueda);
        $ctx = labCtx();

        $pacientes = Paciente::query()
            ->with(['cliente', 'especie', 'raza'])
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('pacientes.idClientes', $ctx->idClientes);
            })
            ->when($this->vista === self::VISTA_HOY, function ($q) {
                $q->whereDate('pacientes.fechhoy', now()->toDateString());
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('pacientes.nombreProtocolo', 'like', "%{$term}%")
                        ->orWhere('pacientes.nombre', 'like', "%{$term}%")
                        ->orWhere('pacientes.propietario', 'like', "%{$term}%")
                        ->orWhere('pacientes.email', 'like', "%{$term}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('pacientes.fechhoy')
            ->orderByDesc('pacientes.nombreProtocolo')
            ->paginate(self::POR_PAGINA);

        return view('livewire.protocolos.paciente-index', compact('pacientes'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
