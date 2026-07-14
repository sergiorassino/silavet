<?php

namespace App\Livewire\Tesoreria;

use App\Models\Cuenta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class CuentaIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cuenta-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Cuenta::query()->findOrFail($id);

        if ($this->cuentaEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: la cuenta tiene subcuentas asociadas.',
                titulo: 'Cuenta en uso'
            );

            return;
        }

        Cuenta::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Cuenta eliminada correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $cuentas = Cuenta::query()
            ->when($term !== '', fn ($q) => $q->where('nombreCuenta', 'like', "%{$term}%"))
            ->orderBy('nombreCuenta')
            ->paginate(self::POR_PAGINA);

        return view('livewire.tesoreria.cuenta-index', compact('cuentas'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function cuentaEnUso(int $id): bool
    {
        if (! Schema::hasTable('cuentasdetalle')) {
            return false;
        }

        return DB::table('cuentasdetalle')
            ->where('idCuentas', $id)
            ->exists();
    }
}
