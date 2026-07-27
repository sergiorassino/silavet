<?php

namespace App\Livewire\Abm\Reactivos;

use App\Models\Reactivo;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class ReactivoIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        $key = 'reactivo-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Reactivo::query()->findOrFail($id);

        if ($this->reactivoEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: el reactivo está asociado a tipos de determinación.',
                titulo: 'Reactivo en uso'
            );

            return;
        }

        Reactivo::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Reactivo eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $reactivos = Reactivo::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where('reactivo', 'like', "%{$term}%");
            })
            ->orderBy('reactivo')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.reactivos.reactivo-index', compact('reactivos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function reactivoEnUso(int $id): bool
    {
        if (! Schema::hasTable('reactivoxdeterminacion') || ! Schema::hasColumn('reactivoxdeterminacion', 'idReactivos')) {
            return false;
        }

        return DB::table('reactivoxdeterminacion')->where('idReactivos', $id)->exists();
    }
}
