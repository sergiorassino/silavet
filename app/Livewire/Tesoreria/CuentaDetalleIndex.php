<?php

namespace App\Livewire\Tesoreria;

use App\Models\CuentaDetalle;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class CuentaDetalleIndex extends Component
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

        $key = 'cuenta-detalle-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        CuentaDetalle::query()->findOrFail($id);

        if ($this->detalleEnUso($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede eliminar: la subcuenta está asociada a movimientos o protocolos.',
                titulo: 'Subcuenta en uso'
            );

            return;
        }

        CuentaDetalle::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Subcuenta eliminada correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $detalles = CuentaDetalle::query()
            ->with(['cuenta:id,nombreCuenta'])
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('nombreCuentasDetalle', 'like', "%{$term}%")
                        ->orWhereHas('cuenta', fn ($c) => $c->where('nombreCuenta', 'like', "%{$term}%"));
                });
            })
            ->orderBy('nombreCuentasDetalle')
            ->paginate(self::POR_PAGINA);

        return view('livewire.tesoreria.cuenta-detalle-index', compact('detalles'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function detalleEnUso(int $id): bool
    {
        if (! Schema::hasTable('pacientes')) {
            return false;
        }

        return DB::table('pacientes')
            ->where('idCuentasdetalle', $id)
            ->exists();
    }
}
