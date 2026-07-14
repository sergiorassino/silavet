<?php

namespace App\Livewire\Abm\Usuarios;

use App\Models\Usuario;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithPagination;

class UsuarioIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        $key = 'usuario-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        if ($id === (int) auth()->id()) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No puede eliminar su propio usuario.',
                titulo: 'Operación no permitida'
            );

            return;
        }

        $usuario = Usuario::query()->findOrFail($id);
        $usuario->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Usuario eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $usuarios = Usuario::query()
            ->with(['rol', 'cliente'])
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('apenom', 'like', "%{$term}%")
                        ->orWhere('dni', 'like', "%{$term}%")
                        ->orWhere('cuit', 'like', "%{$term}%")
                        ->orWhere('razonSocial', 'like', "%{$term}%")
                        ->orWhereHas('rol', fn ($r) => $r->where('rol', 'like', "%{$term}%"))
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"));
                });
            })
            ->orderBy('apenom')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.usuarios.usuario-index', compact('usuarios'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
