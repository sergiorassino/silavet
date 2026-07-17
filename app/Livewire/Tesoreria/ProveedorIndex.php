<?php

namespace App\Livewire\Tesoreria;

use App\Models\Concepto;
use App\Models\Proveedor;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ABM de proveedores (variante tesoreria_movimientos / labvetciudad).
 */
class ProveedorIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public string $filtroConcepto = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaMovimientos(), 404);
        abort_unless(Schema::hasTable('proveedores'), 404);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroConcepto(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaMovimientos(), 404);

        $key = 'proveedor-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Proveedor::query()->findOrFail($id);

        if ($motivo = $this->motivoBloqueoEliminacion($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: $motivo,
                titulo: 'Proveedor en uso'
            );

            return;
        }

        Proveedor::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Proveedor eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);
        $idConceptoFiltro = $this->filtroConcepto !== '' ? (int) $this->filtroConcepto : null;

        $proveedores = Proveedor::query()
            ->with(['concepto:id,concepto'])
            ->leftJoin('conceptos', 'proveedores.idConceptos', '=', 'conceptos.id')
            ->when($idConceptoFiltro, fn ($q) => $q->where('proveedores.idConceptos', $idConceptoFiltro))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('proveedores.proveedor', 'like', "%{$term}%")
                        ->orWhere('proveedores.cuit', 'like', "%{$term}%")
                        ->orWhere('conceptos.concepto', 'like', "%{$term}%");
                });
            })
            ->orderBy('conceptos.concepto')
            ->orderBy('proveedores.proveedor')
            ->select('proveedores.*')
            ->paginate(self::POR_PAGINA);

        $conceptos = Schema::hasTable('conceptos')
            ? Concepto::query()->orderBy('orden')->orderBy('concepto')->get(['id', 'concepto'])
            : collect();

        return view('livewire.tesoreria.proveedor-index', compact('proveedores', 'conceptos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function motivoBloqueoEliminacion(int $id): ?string
    {
        if (Schema::hasTable('movimientos') && Schema::hasColumn('movimientos', 'idProveedores')) {
            if (DB::table('movimientos')->where('idProveedores', $id)->exists()) {
                return 'No se puede eliminar: el proveedor tiene movimientos asociados.';
            }
        }

        return null;
    }
}
