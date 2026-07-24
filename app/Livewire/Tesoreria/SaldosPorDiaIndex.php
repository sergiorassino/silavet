<?php

namespace App\Livewire\Tesoreria;

use App\Models\Movimiento;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\SaldosPorDiaConsulta;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Saldos por día (variante tesoreria_pacientes / labvetciudad).
 */
class SaldosPorDiaIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public ?string $diaExpandido = null;

    public ?string $cuentaExpandida = null;

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);
        abort_unless(Schema::hasTable('movimientos'), 404);

        $hoy = now();
        $this->fechaDesde = $hoy->copy()->startOfMonth()->toDateString();
        $this->fechaHasta = $hoy->toDateString();
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
        $this->cerrarExpansiones();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
        $this->cerrarExpansiones();
    }

    public function toggleDia(string $fecha): void
    {
        if ($this->diaExpandido === $fecha) {
            $this->cerrarExpansiones();

            return;
        }

        $this->diaExpandido = $fecha;
        $this->cuentaExpandida = null;
    }

    public function toggleCuenta(string $fecha, int $idCuenta): void
    {
        $clave = $fecha.':'.$idCuenta;
        if ($this->cuentaExpandida === $clave) {
            $this->cuentaExpandida = null;

            return;
        }

        $this->diaExpandido = $fecha;
        $this->cuentaExpandida = $clave;
    }

    public function render()
    {
        $desde = $this->fechaNormalizada($this->fechaDesde)
            ?? now()->startOfMonth()->toDateString();
        $hasta = $this->fechaNormalizada($this->fechaHasta)
            ?? now()->toDateString();

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $todosLosDias = SaldosPorDiaConsulta::diasConSaldos($desde, $hasta);
        $cuentas = SaldosPorDiaConsulta::cuentas();

        $page = max(1, (int) $this->getPage());
        $total = count($todosLosDias);
        $slice = array_slice($todosLosDias, ($page - 1) * self::POR_PAGINA, self::POR_PAGINA);

        $dias = new LengthAwarePaginator(
            $slice,
            $total,
            self::POR_PAGINA,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        $movimientosDetalle = collect();
        $sumaDetalle = 0.0;
        $cuentaDetalleId = null;
        $fechaDetalle = null;

        if ($this->cuentaExpandida !== null && str_contains($this->cuentaExpandida, ':')) {
            [$fechaDetalle, $idStr] = explode(':', $this->cuentaExpandida, 2);
            $cuentaDetalleId = (int) $idStr;

            $movimientosDetalle = Movimiento::query()
                ->with([
                    'cuenta:id,nombreMedioPago',
                    'tipoMovimiento:id,tipoMovimiento',
                    'cliente:idClientes,nombre',
                    'paciente:idPacientes,nombre',
                    'concepto:id,concepto',
                    'proveedor:id,proveedor',
                ])
                ->whereDate('fechhora', $fechaDetalle)
                ->where('idCuentas', $cuentaDetalleId)
                ->orderBy('fechhora')
                ->orderBy('id')
                ->get();

            $sumaDetalle = round((float) $movimientosDetalle->sum('monto'), 2);
        }

        return view('livewire.tesoreria.saldos-por-dia-index', [
            'dias' => $dias,
            'cuentas' => $cuentas,
            'movimientosDetalle' => $movimientosDetalle,
            'sumaDetalle' => $sumaDetalle,
            'fechaDetalle' => $fechaDetalle,
            'cuentaDetalleId' => $cuentaDetalleId,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function cerrarExpansiones(): void
    {
        $this->diaExpandido = null;
        $this->cuentaExpandida = null;
    }

    private function fechaNormalizada(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
