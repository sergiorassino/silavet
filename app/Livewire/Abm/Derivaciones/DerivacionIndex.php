<?php

namespace App\Livewire\Abm\Derivaciones;

use App\Models\Derivacion;
use App\Support\PermisosIaCatalog;
use App\Support\Tipodeterminaciones\TipodeterminacionesGridConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class DerivacionIndex extends Component
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

        $key = 'derivacion-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Derivacion::query()->findOrFail($id);

        if ($this->derivacionEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: el centro está asociado a determinaciones o tipos de análisis.',
                titulo: 'Centro en uso'
            );

            return;
        }

        Derivacion::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Centro de derivación eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $centros = Derivacion::query()
            ->when($term !== '', fn ($q) => $q->where('derivacion', 'like', "%{$term}%"))
            ->orderBy('derivacion')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.derivaciones.derivacion-index', compact('centros'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function derivacionEnUso(int $id): bool
    {
        if (Schema::hasTable('determinaciones')
            && Schema::hasColumn('determinaciones', 'idDerivaciones')
            && DB::table('determinaciones')->where('idDerivaciones', $id)->exists()) {
            return true;
        }

        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()
            && Schema::hasTable('tipodeterminaciones')
            && Schema::hasColumn('tipodeterminaciones', 'destino')
            && DB::table('tipodeterminaciones')->where('destino', $id)->exists()) {
            return true;
        }

        return false;
    }
}
