<?php

namespace App\Livewire\Abm\Grupos;

use App\Models\Grupo;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class GrupoIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'grupo-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Grupo::query()->findOrFail($id);

        if ($this->grupoEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: el grupo tiene ítems de informe asociados.',
                titulo: 'Grupo en uso'
            );

            return;
        }

        Grupo::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Grupo eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $grupos = Grupo::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('nombreGrupo', 'like', "%{$term}%")
                        ->orWhere('orden', 'like', "%{$term}%");
                });
            })
            ->orderBy('orden')
            ->orderBy('nombreGrupo')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.grupos.grupo-index', compact('grupos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function grupoEnUso(int $id): bool
    {
        if (Schema::hasTable('itemsinforme')) {
            $enItems = DB::table('itemsinforme')
                ->where('idGrupos', $id)
                ->exists();

            if ($enItems) {
                return true;
            }
        }

        if (Schema::hasTable('renglones')) {
            return DB::table('renglones')
                ->where('idGrupos', $id)
                ->exists();
        }

        return false;
    }
}
