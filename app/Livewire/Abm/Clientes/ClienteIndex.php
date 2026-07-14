<?php

namespace App\Livewire\Abm\Clientes;

use App\Models\Cliente;
use App\Models\Paciente;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::CLIENTES), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::CLIENTES), 403);

        $key = 'cliente-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Cliente::query()->findOrFail($id);

        if ($id === Paciente::ID_CLIENTES_EGRESO) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar el cliente interno de egresos.',
                titulo: 'Cliente protegido'
            );

            return;
        }

        if ($this->clienteEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: el cliente tiene protocolos, usuarios u otros registros asociados.',
                titulo: 'Cliente en uso'
            );

            return;
        }

        Cliente::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Cliente eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $clientes = Cliente::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('nombre', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('cuit', 'like', "%{$term}%")
                        ->orWhere('telefono1', 'like', "%{$term}%")
                        ->orWhere('telefono2', 'like', "%{$term}%")
                        ->orWhere('whatsapp', 'like', "%{$term}%")
                        ->orWhere('direccion', 'like', "%{$term}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.clientes.cliente-index', compact('clientes'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function clienteEnUso(int $id): bool
    {
        $tablas = [
            'pacientes',
            'usuarios',
            'estimacioncostos',
            'notificaciones',
            'determinaciones',
            'renglones',
        ];

        foreach ($tablas as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'idClientes')) {
                continue;
            }

            if (DB::table($tabla)->where('idClientes', $id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
