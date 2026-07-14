<?php

namespace App\Livewire\Abm\Especies;

use App\Models\Especie;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class EspecieIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::ESPECIES), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::ESPECIES), 403);

        $key = 'especie-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Especie::query()->findOrFail($id);

        if ($this->especieEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: la especie tiene razas o protocolos asociados.',
                titulo: 'Especie en uso'
            );

            return;
        }

        Especie::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Especie eliminada correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $especies = Especie::query()
            ->when($term !== '', fn ($q) => $q->where('nombre', 'like', "%{$term}%"))
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.especies.especie-index', compact('especies'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function especieEnUso(int $id): bool
    {
        $tablas = ['razas', 'pacientes'];

        foreach ($tablas as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'idEspecies')) {
                continue;
            }

            if (DB::table($tabla)->where('idEspecies', $id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
