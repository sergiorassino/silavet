<?php

namespace App\Livewire\Tesoreria;

use App\Models\Concepto;
use App\Models\TipoMovimiento;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ABM de conceptos (variante tesoreria_movimientos / labvetciudad).
 */
class ConceptoIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    /** @var list<string> */
    private const COLUMNAS_ORDEN = ['tipo', 'concepto', 'orden'];

    public string $busqueda = '';

    public string $filtroTipo = '';

    public string $ordenarPor = 'tipo';

    public string $direccionOrden = 'asc';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaMovimientos(), 404);
        abort_unless(Schema::hasTable('conceptos'), 404);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo(): void
    {
        $this->resetPage();
    }

    public function ordenar(string $columna): void
    {
        if (! in_array($columna, self::COLUMNAS_ORDEN, true)) {
            return;
        }

        if ($this->ordenarPor === $columna) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $columna;
            $this->direccionOrden = 'asc';
        }

        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaMovimientos(), 404);

        $key = 'concepto-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        Concepto::query()->findOrFail($id);

        if ($motivo = $this->motivoBloqueoEliminacion($id)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: $motivo,
                titulo: 'Concepto en uso'
            );

            return;
        }

        Concepto::query()->whereKey($id)->delete();

        $this->dispatch('vl-swal-exito', mensaje: 'Concepto eliminado correctamente.');
    }

    public function render()
    {
        $term = trim($this->busqueda);
        $idTipoFiltro = $this->filtroTipo !== '' ? (int) $this->filtroTipo : null;

        $dir = $this->direccionOrden === 'desc' ? 'desc' : 'asc';
        $columna = in_array($this->ordenarPor, self::COLUMNAS_ORDEN, true)
            ? $this->ordenarPor
            : 'tipo';

        $conceptos = Concepto::query()
            ->with(['tipoMovimiento:id,tipoMovimiento'])
            ->leftJoin('tipomovimiento', 'conceptos.tipoConcepto', '=', 'tipomovimiento.id')
            ->when($idTipoFiltro, fn ($q) => $q->where('conceptos.tipoConcepto', $idTipoFiltro))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('conceptos.concepto', 'like', "%{$term}%")
                        ->orWhere('tipomovimiento.tipoMovimiento', 'like', "%{$term}%");
                });
            })
            ->when($columna === 'concepto', function ($q) use ($dir) {
                $q->orderBy('conceptos.concepto', $dir)
                    ->orderBy('tipomovimiento.tipoMovimiento')
                    ->orderBy('conceptos.orden');
            })
            ->when($columna === 'orden', function ($q) use ($dir) {
                $q->orderBy('conceptos.orden', $dir)
                    ->orderBy('tipomovimiento.tipoMovimiento')
                    ->orderBy('conceptos.concepto');
            })
            ->when($columna === 'tipo', function ($q) use ($dir) {
                $q->orderBy('tipomovimiento.tipoMovimiento', $dir)
                    ->orderBy('conceptos.orden')
                    ->orderBy('conceptos.concepto');
            })
            ->select('conceptos.*')
            ->paginate(self::POR_PAGINA);

        $tipos = Schema::hasTable('tipomovimiento')
            ? TipoMovimiento::query()->orderBy('tipoMovimiento')->get(['id', 'tipoMovimiento'])
            : collect();

        return view('livewire.tesoreria.concepto-index', compact('conceptos', 'tipos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function motivoBloqueoEliminacion(int $id): ?string
    {
        if (Schema::hasTable('movimientos') && Schema::hasColumn('movimientos', 'idConcepto')) {
            if (DB::table('movimientos')->where('idConcepto', $id)->exists()) {
                return 'No se puede eliminar: el concepto tiene movimientos asociados.';
            }
        }

        if (Schema::hasTable('proveedores') && Schema::hasColumn('proveedores', 'idConceptos')) {
            if (DB::table('proveedores')->where('idConceptos', $id)->exists()) {
                return 'No se puede eliminar: el concepto tiene proveedores asociados.';
            }
        }

        return null;
    }
}
