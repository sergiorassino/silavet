<?php

namespace App\Support\CuentaCorriente;

use App\Models\Cliente;
use App\Models\Movimiento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consultas de cuenta corriente para la variante `tesoreria_pacientes`
 * (labvetciudad y afines). El saldo se calcula sobre `movimientos`,
 * filtrando por `idCuentas = id_cuenta_cc` (default: 1 = CC).
 *
 * No modificar CuentaCorrienteConsulta (variante NeoLab / pacientes).
 */
final class CuentaCorrienteMovimientosConsulta
{
    /**
     * `id` de mediodepago que representa la cuenta corriente del cliente.
     * Configurable en `config/tenant.php` → `cuenta_corriente.id_cuenta_cc`.
     */
    public static function idCuentaCc(): int
    {
        return max(1, (int) config('tenant.cuenta_corriente.id_cuenta_cc', 1));
    }

    /**
     * @return Collection<int, Cliente&object{saldo_total: float}>
     */
    public static function clientesListado(string $busqueda): Collection
    {
        return self::queryClientesConSaldo($busqueda)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Cliente>
     */
    private static function queryClientesConSaldo(string $busqueda)
    {
        $idCuentaCc = self::idCuentaCc();
        $term = trim($busqueda);

        $saldoSubquery = DB::table('movimientos')
            ->select('idClientes')
            ->selectRaw('SUM(monto) as saldo_total')
            ->where('idCuentas', $idCuentaCc)
            ->groupBy('idClientes');

        return Cliente::query()
            ->leftJoinSub($saldoSubquery, 'saldos', 'saldos.idClientes', '=', 'clientes.idClientes')
            ->select('clientes.*')
            ->selectRaw('COALESCE(saldos.saldo_total, 0) as saldo_total')
            ->where('clientes.idClientes', '>', 1)
            ->when(Schema::hasColumn('clientes', 'tipoCliente'), function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('clientes.tipoCliente')
                        ->orWhere('clientes.tipoCliente', '!=', 1);
                });
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('clientes.nombre', 'like', "%{$term}%")
                        ->orWhere('clientes.direccion', 'like', "%{$term}%")
                        ->orWhere('clientes.telefono1', 'like', "%{$term}%")
                        ->orWhere('clientes.telefono2', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('saldo_total')
            ->orderBy('clientes.nombre');
    }

    /**
     * Saldo actual del cliente: SUM(monto) en la cuenta CC.
     */
    public static function saldoClienteHoy(int $idClientes): float
    {
        return round((float) DB::table('movimientos')
            ->where('idClientes', $idClientes)
            ->where('idCuentas', self::idCuentaCc())
            ->sum('monto'), 2);
    }

    /**
     * Saldo CC acumulado de todos los movimientos anteriores a `$fechaDesde`.
     * Devuelve null si no hay fecha de inicio (sin filtro de inicio).
     */
    public static function saldoAnteriorAFecha(int $idClientes, ?string $fechaDesde): ?float
    {
        $desde = trim((string) $fechaDesde);
        if ($desde === '') {
            return null;
        }

        $fechaCorte = Carbon::parse($desde)->toDateString();

        return round((float) DB::table('movimientos')
            ->where('idClientes', $idClientes)
            ->where('idCuentas', self::idCuentaCc())
            ->whereDate('fechhora', '<', $fechaCorte)
            ->sum('monto'), 2);
    }

    /**
     * Movimientos de cuenta corriente del cliente en el período.
     * Solo `idCuentas = id_cuenta_cc` (mismo criterio que saldo / saldo anterior).
     * Orden `fechhora` DESC.
     *
     * @return Collection<int, object{
     *   id: int,
     *   idClientes: int,
     *   etiquetaPaciente: string,
     *   idCuentas: int,
     *   cuentaLabel: string,
     *   concepto: string,
     *   fechhora: string,
     *   monto: float,
     *   obs: string,
     *   esNegativo: bool,
     * }>
     */
    public static function movimientosCliente(
        int $idClientes,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
    ): Collection {
        $desde = trim((string) $fechaDesde);
        $hasta = trim((string) $fechaHasta);
        $idCuentaCc = self::idCuentaCc();

        return Movimiento::query()
            ->with(['cuenta', 'concepto'])
            ->where('idClientes', $idClientes)
            ->where('idCuentas', $idCuentaCc)
            ->when($desde !== '', fn ($q) => $q->whereDate('fechhora', '>=', Carbon::parse($desde)->toDateString()))
            ->when($hasta !== '', fn ($q) => $q->whereDate('fechhora', '<=', Carbon::parse($hasta)->toDateString()))
            ->orderByDesc('fechhora')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Movimiento $m) => (object) [
                'id' => (int) $m->id,
                'idClientes' => (int) ($m->idClientes ?? 0),
                'etiquetaPaciente' => trim((string) ($m->paciente?->nombre ?? '')),
                'idCuentas' => (int) ($m->idCuentas ?? 0),
                'cuentaLabel' => trim((string) ($m->cuenta?->nombreMedioPago ?? '')),
                'concepto' => trim((string) ($m->concepto?->concepto ?? '')),
                'fechhora' => $m->fechhora?->format('Y-m-d H:i:s') ?? '',
                'monto' => round((float) ($m->monto ?? 0), 2),
                'obs' => trim((string) ($m->obs ?? '')),
                'esNegativo' => round((float) ($m->monto ?? 0), 2) < 0,
            ]);
    }

    /**
     * @param  Collection<int, object>  $filas
     * @return array{total_monto: float, cantidad: int}
     */
    public static function resumenMovimientos(Collection $filas): array
    {
        return [
            'total_monto' => round($filas->sum(fn ($f) => (float) $f->monto), 2),
            'cantidad' => $filas->count(),
        ];
    }

    /**
     * Saldo acumulado a la fecha de corte del período: saldo anterior + total del período.
     * Si no hay fecha desde, el saldo anterior se toma como 0.
     */
    public static function totalALaFecha(?float $saldoAnterior, float $totalPeriodo): float
    {
        return round(($saldoAnterior ?? 0.0) + $totalPeriodo, 2);
    }

    public static function etiquetaPeriodo(?string $fechaDesde, ?string $fechaHasta): string
    {
        $desde = trim((string) $fechaDesde);
        $hasta = trim((string) $fechaHasta);

        if ($desde === '' && $hasta === '') {
            return 'Todo el historial';
        }
        if ($desde !== '' && $hasta !== '') {
            return Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y');
        }
        if ($desde !== '') {
            return 'Desde '.Carbon::parse($desde)->format('d/m/Y');
        }

        return 'Hasta '.Carbon::parse($hasta)->format('d/m/Y');
    }

    public static function formatearMoneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
