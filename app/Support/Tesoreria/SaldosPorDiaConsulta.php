<?php

namespace App\Support\Tesoreria;

use App\Models\MedioDePago;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saldos diarios por cuenta (mediodepago) a partir de movimientos.
 *
 * @phpstan-type CuentaArray array{id: int, nombre: string, abrev: string}
 * @phpstan-type SaldoCuentaArray array{id: int, nombre: string, abrev: string, inicial: float, final: float, delta: float}
 * @phpstan-type DiaArray array{fecha: string, cuentas: list<SaldoCuentaArray>}
 */
final class SaldosPorDiaConsulta
{
    /**
     * @return Collection<int, CuentaArray>
     */
    public static function cuentas(): Collection
    {
        if (! Schema::hasTable('mediodepago')) {
            return collect();
        }

        $query = MedioDePago::query();
        if (Schema::hasColumn('mediodepago', 'orden')) {
            $query->orderBy('orden');
        }
        $query->orderBy('nombreMedioPago');

        return $query
            ->where('id', '!=', 4)
            ->get(['id', 'nombreMedioPago'])
            ->map(fn (MedioDePago $m) => [
                'id' => (int) $m->id,
                'nombre' => (string) ($m->nombreMedioPago ?? ''),
                'abrev' => self::abreviarNombre((string) ($m->nombreMedioPago ?? '')),
            ])
            ->values();
    }

    /**
     * Días con movimiento en el rango, con saldos inicial/final por cuenta.
     * Orden ascendente por fecha. El saldo de apertura usa todo el historial previo a fechaDesde.
     *
     * @return list<DiaArray>
     */
    public static function diasConSaldos(string $fechaDesde, string $fechaHasta): array
    {
        $cuentas = self::cuentas();
        if ($cuentas->isEmpty() || ! Schema::hasTable('movimientos')) {
            return [];
        }

        $desde = Carbon::parse($fechaDesde)->toDateString();
        $hasta = Carbon::parse($fechaHasta)->toDateString();

        $ids = $cuentas->pluck('id')->all();

        $apertura = self::saldosAntesDe($desde, $ids);
        $deltas = self::deltasPorDiaYCuenta($desde, $hasta, $ids);

        $fechas = collect($deltas)->keys()->sort()->values()->all();

        $running = $apertura;
        $resultado = [];

        foreach ($fechas as $fecha) {
            $filaCuentas = [];
            foreach ($cuentas as $cuenta) {
                $id = $cuenta['id'];
                $inicial = round((float) ($running[$id] ?? 0.0), 2);
                $delta = round((float) ($deltas[$fecha][$id] ?? 0.0), 2);
                $final = round($inicial + $delta, 2);
                $running[$id] = $final;

                $filaCuentas[] = [
                    'id' => $id,
                    'nombre' => $cuenta['nombre'],
                    'abrev' => $cuenta['abrev'],
                    'inicial' => $inicial,
                    'final' => $final,
                    'delta' => $delta,
                ];
            }

            $resultado[] = [
                'fecha' => $fecha,
                'cuentas' => $filaCuentas,
            ];
        }

        return $resultado;
    }

    public static function formatearMonto(float $monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    public static function abreviarNombre(string $nombre): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return '—';
        }

        $lower = mb_strtolower($nombre);
        $map = [
            'cuenta corriente' => 'CTA. CORR.',
            'efectivo' => 'EFECT.',
            'banco santander' => 'SANT.',
            'bando santander' => 'SANT.',
            'santander' => 'SANT.',
            'mercado pago' => 'M. PAGO',
        ];
        if (isset($map[$lower])) {
            return $map[$lower];
        }

        $palabras = preg_split('/\s+/u', $nombre) ?: [];
        if (count($palabras) === 1) {
            $w = mb_strtoupper($palabras[0]);

            return mb_strlen($w) <= 8 ? $w : (mb_substr($w, 0, 6).'.');
        }

        $partes = [];
        foreach ($palabras as $p) {
            $p = mb_strtoupper($p);
            if (mb_strlen($p) <= 2) {
                continue;
            }
            $partes[] = mb_substr($p, 0, min(5, mb_strlen($p))).'.';
            if (count($partes) >= 2) {
                break;
            }
        }

        return $partes !== [] ? implode(' ', $partes) : mb_strtoupper(mb_substr($nombre, 0, 8));
    }

    /**
     * @param  list<int>  $idsCuentas
     * @return array<int, float>
     */
    private static function saldosAntesDe(string $fechaDesde, array $idsCuentas): array
    {
        $rows = DB::table('movimientos')
            ->selectRaw('idCuentas, COALESCE(SUM(monto), 0) as total')
            ->whereIn('idCuentas', $idsCuentas)
            ->whereDate('fechhora', '<', $fechaDesde)
            ->groupBy('idCuentas')
            ->get();

        $mapa = array_fill_keys($idsCuentas, 0.0);
        foreach ($rows as $row) {
            $mapa[(int) $row->idCuentas] = round((float) $row->total, 2);
        }

        return $mapa;
    }

    /**
     * @param  list<int>  $idsCuentas
     * @return array<string, array<int, float>> fecha => [idCuenta => delta]
     */
    private static function deltasPorDiaYCuenta(string $desde, string $hasta, array $idsCuentas): array
    {
        $rows = DB::table('movimientos')
            ->selectRaw('DATE(fechhora) as dia, idCuentas, COALESCE(SUM(monto), 0) as total')
            ->whereIn('idCuentas', $idsCuentas)
            ->whereDate('fechhora', '>=', $desde)
            ->whereDate('fechhora', '<=', $hasta)
            ->groupByRaw('DATE(fechhora), idCuentas')
            ->orderBy('dia')
            ->get();

        $mapa = [];
        foreach ($rows as $row) {
            $dia = (string) $row->dia;
            $id = (int) $row->idCuentas;
            $mapa[$dia][$id] = round((float) $row->total, 2);
        }

        return $mapa;
    }
}
