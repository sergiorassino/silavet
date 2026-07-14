<?php

namespace App\Livewire\Abm\Requerimientos;

use App\Models\Requerimiento;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class RequerimientoIndex extends Component
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

        $key = 'requerimiento-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Requerimiento::query()->findOrFail($id);

        if ($this->requerimientoEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: el procedimiento está asociado a tipos de determinación.',
                titulo: 'Procedimiento en uso'
            );

            return;
        }

        Requerimiento::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Procedimiento eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $requerimientos = Requerimiento::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('titulo', 'like', "%{$term}%")
                        ->orWhere('requerimiento', 'like', "%{$term}%");
                });
            })
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.requerimientos.requerimiento-index', compact('requerimientos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function requerimientoEnUso(int $id): bool
    {
        if (! Schema::hasTable('reqxtipodet') || ! Schema::hasColumn('reqxtipodet', 'idRequerimientos')) {
            return false;
        }

        return DB::table('reqxtipodet')->where('idRequerimientos', $id)->exists();
    }
}
