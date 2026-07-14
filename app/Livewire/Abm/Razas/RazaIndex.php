<?php

namespace App\Livewire\Abm\Razas;

use App\Models\Especie;
use App\Models\Raza;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class RazaIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public string $filtroEspecie = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::ESPECIES), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEspecie(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::ESPECIES), 403);

        $key = 'raza-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Raza::query()->findOrFail($id);

        if ($this->razaEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: la raza tiene protocolos asociados.',
                titulo: 'Raza en uso'
            );

            return;
        }

        Raza::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Raza eliminada correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);
        $idEspecieFiltro = $this->filtroEspecie !== '' ? (int) $this->filtroEspecie : null;

        $razas = Raza::query()
            ->with(['especie:idEspecies,nombre'])
            ->leftJoin('especies', 'razas.idEspecies', '=', 'especies.idEspecies')
            ->when($idEspecieFiltro, fn ($q) => $q->where('razas.idEspecies', $idEspecieFiltro))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('razas.nombre', 'like', "%{$term}%")
                        ->orWhere('especies.nombre', 'like', "%{$term}%");
                });
            })
            ->orderBy('especies.nombre')
            ->orderBy('razas.nombre')
            ->select('razas.*')
            ->paginate(self::POR_PAGINA);

        $especies = Especie::query()
            ->orderBy('nombre')
            ->get(['idEspecies', 'nombre']);

        return view('livewire.abm.razas.raza-index', compact('razas', 'especies'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function razaEnUso(int $id): bool
    {
        if (! Schema::hasTable('pacientes') || ! Schema::hasColumn('pacientes', 'idRazas')) {
            return false;
        }

        return DB::table('pacientes')->where('idRazas', $id)->exists();
    }
}
