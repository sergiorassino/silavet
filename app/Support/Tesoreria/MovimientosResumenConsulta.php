<?php

namespace App\Support\Tesoreria;

use App\Models\MedioDePago;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resumen de caja alineado al filtro del listado (variante NeoLab / pacientes).
 *
 * @phpstan-type MedioResumen array{
 *     id: int,
 *     nombre: string,
 *     es_efectivo: bool,
 *     ingresos: float,
 *     egresos: float,
 *     saldo: float,
 *     etiqueta_ingreso: string,
 *     etiqueta_egreso: string,
 *     etiqueta_saldo: string
 * }
 * @phpstan-type ResumenArray array{
 *     fecha_desde: ?string,
 *     fecha_hasta: ?string,
 *     total_devengado: float,
 *     total_ingresos: float,
 *     total_egresos: float,
 *     saldo_total: float,
 *     medios: list<MedioResumen>
 * }
 */
final class MovimientosResumenConsulta
{
    /**
     * @param  ?string  $fechaDesde  Y-m-d inclusive; null = sin límite inferior
     * @param  ?string  $fechaHasta  Y-m-d inclusive; null = sin límite superior
     * @return ResumenArray
     */
    public static function paraRango(?string $fechaDesde, ?string $fechaHasta): array
    {
        $medios = self::medios();
        $ingresosPorMedio = self::totalesPorMedioEnRango($fechaDesde, $fechaHasta, Paciente::TIPO_INGRESO);
        $egresosPorMedio = self::totalesPorMedioEnRango($fechaDesde, $fechaHasta, Paciente::TIPO_EGRESO);
        // Saldos: acumulado histórico hasta el tope del filtro (no el neto del período).
        $saldosPorMedio = self::saldosAcumuladosHasta($fechaHasta);

        $filas = [];
        $totalIngresos = 0.0;
        $totalEgresos = 0.0;
        $saldoTotal = 0.0;

        foreach ($medios as $medio) {
            $id = $medio['id'];
            $nombre = $medio['nombre'];
            $esEfectivo = self::esEfectivo($nombre);
            $ingresos = round((float) ($ingresosPorMedio[$id] ?? 0.0), 2);
            $egresos = round((float) ($egresosPorMedio[$id] ?? 0.0), 2);
            $saldo = round((float) ($saldosPorMedio[$id] ?? 0.0), 2);

            $totalIngresos += $ingresos;
            $totalEgresos += $egresos;
            $saldoTotal += $saldo;

            $filas[] = [
                'id' => $id,
                'nombre' => $nombre,
                'es_efectivo' => $esEfectivo,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'saldo' => $saldo,
                'etiqueta_ingreso' => $esEfectivo
                    ? 'Total Cobrado en Efectivo'
                    : 'Total Cobrado con Transferencia '.$nombre,
                'etiqueta_egreso' => $esEfectivo
                    ? 'Total Egresos en Efectivo'
                    : 'Total Egresos con Transferencia '.$nombre,
                'etiqueta_saldo' => 'Saldo Cuenta '.$nombre,
            ];
        }

        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'total_devengado' => self::totalDevengadoEnRango($fechaDesde, $fechaHasta),
            'total_ingresos' => round($totalIngresos, 2),
            'total_egresos' => round($totalEgresos, 2),
            'saldo_total' => round($saldoTotal, 2),
            'medios' => $filas,
        ];
    }

    public static function formatearMonto(float $monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    private static function medios(): array
    {
        if (! Schema::hasTable('mediodepago')) {
            return [];
        }

        return MedioDePago::query()
            ->orderBy('id')
            ->get(['id', 'nombreMedioPago'])
            ->map(fn (MedioDePago $m) => [
                'id' => (int) $m->id,
                'nombre' => (string) ($m->nombreMedioPago ?? ''),
            ])
            ->all();
    }

    /**
     * Suma de precios de protocolos (totales de determinaciones) en el rango.
     */
    private static function totalDevengadoEnRango(?string $desde, ?string $hasta): float
    {
        if (! Schema::hasTable('pacientes')) {
            return 0.0;
        }

        // Preferir suma de determinaciones; si no hay tabla, usar pacientes.precio.
        if (Schema::hasTable('determinaciones')) {
            $q = DB::table('determinaciones')
                ->join('pacientes', 'pacientes.idPacientes', '=', 'determinaciones.idPacientes')
                ->whereNotIn('pacientes.tipoRegistro', [
                    Paciente::TIPO_INGRESO,
                    Paciente::TIPO_EGRESO,
                ]);
            self::aplicarRangoFechas($q, 'pacientes.fechhoy', $desde, $hasta);
            $totalDet = round((float) $q->sum('determinaciones.precio'), 2);
            if ($totalDet > 0) {
                return $totalDet;
            }
        }

        $q = DB::table('pacientes')
            ->whereNotIn('tipoRegistro', [
                Paciente::TIPO_INGRESO,
                Paciente::TIPO_EGRESO,
            ]);
        self::aplicarRangoFechas($q, 'fechhoy', $desde, $hasta);

        return round((float) $q->sum('precio'), 2);
    }

    /**
     * @return array<int, float>
     */
    private static function totalesPorMedioEnRango(?string $desde, ?string $hasta, int $tipoRegistro): array
    {
        if (! Schema::hasTable('pacientes')) {
            return [];
        }

        $q = DB::table('pacientes')
            ->selectRaw('idMediodepago, COALESCE(SUM(pagado), 0) as total')
            ->where('tipoRegistro', $tipoRegistro)
            ->where('idMediodepago', '>', 0);
        self::aplicarRangoFechas($q, 'fechhoy', $desde, $hasta);

        $mapa = [];
        foreach ($q->groupBy('idMediodepago')->get() as $row) {
            $mapa[(int) $row->idMediodepago] = round((float) $row->total, 2);
        }

        return $mapa;
    }

    /**
     * Saldo acumulado por medio hasta `$fechaHasta` inclusive (ingresos − egresos).
     * Si `$fechaHasta` es null, incluye todo el historial.
     * No usa `$fechaDesde`: el saldo es el stock de la cuenta a esa fecha.
     *
     * @return array<int, float>
     */
    private static function saldosAcumuladosHasta(?string $fechaHasta): array
    {
        if (! Schema::hasTable('pacientes')) {
            return [];
        }

        $q = DB::table('pacientes')
            ->selectRaw(
                'idMediodepago,
                 COALESCE(SUM(CASE
                    WHEN tipoRegistro = ? THEN pagado
                    WHEN tipoRegistro = ? THEN -pagado
                    ELSE 0
                 END), 0) as total',
                [Paciente::TIPO_INGRESO, Paciente::TIPO_EGRESO]
            )
            ->whereIn('tipoRegistro', [Paciente::TIPO_INGRESO, Paciente::TIPO_EGRESO])
            ->where('idMediodepago', '>', 0);

        if ($fechaHasta !== null) {
            $q->whereDate('fechhoy', '<=', $fechaHasta);
        }

        $mapa = [];
        foreach ($q->groupBy('idMediodepago')->get() as $row) {
            $mapa[(int) $row->idMediodepago] = round((float) $row->total, 2);
        }

        return $mapa;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $q
     */
    private static function aplicarRangoFechas($q, string $columna, ?string $desde, ?string $hasta): void
    {
        if ($desde !== null) {
            $q->whereDate($columna, '>=', $desde);
        }
        if ($hasta !== null) {
            $q->whereDate($columna, '<=', $hasta);
        }
    }

    private static function esEfectivo(string $nombre): bool
    {
        return mb_stripos($nombre, 'efectivo') !== false;
    }
}
