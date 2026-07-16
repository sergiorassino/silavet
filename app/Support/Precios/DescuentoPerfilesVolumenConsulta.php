<?php

namespace App\Support\Precios;

use App\Models\Determinacion;
use App\Models\Paciente;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class DescuentoPerfilesVolumenConsulta
{
    /**
     * Umbrales de perfiles en el mes → % de descuento del mes siguiente.
     *
     * @var array<int, float>
     */
    public const UMBRALES = [
        25 => 2.5,
        50 => 5.0,
        100 => 8.0,
        150 => 10.0,
    ];

    /** @var array<string, int> */
    private static array $cacheCantidad = [];

    /** @var array<string, float> */
    private static array $cacheDescuentos = [];

    public static function cantidadPerfilesMesAnterior(int $idClientes, CarbonInterface $fechaReferencia): int
    {
        $mesAnterior = $fechaReferencia->copy()->startOfMonth()->subMonth();

        return self::cantidadPerfilesEnMes($idClientes, $mesAnterior);
    }

    public static function cantidadPerfilesMesActual(int $idClientes, CarbonInterface $fechaReferencia): int
    {
        return self::cantidadPerfilesEnMes($idClientes, $fechaReferencia->copy()->startOfMonth());
    }

    public static function cantidadPerfilesEnMes(int $idClientes, CarbonInterface $mes): int
    {
        if (! self::tablasDisponibles()) {
            return 0;
        }

        $inicioMes = $mes->copy()->startOfMonth();
        $clave = "{$idClientes}:{$inicioMes->format('Y-m')}";

        if (array_key_exists($clave, self::$cacheCantidad)) {
            return self::$cacheCantidad[$clave];
        }

        $desde = $inicioMes->toDateString();
        $hasta = $inicioMes->copy()->endOfMonth()->toDateString();

        $cantidad = (int) Determinacion::query()
            ->join('pacientes', 'determinaciones.idPacientes', '=', 'pacientes.idPacientes')
            ->join('tipodeterminaciones', 'determinaciones.idTipodeterminaciones', '=', 'tipodeterminaciones.idTipodeterminaciones')
            ->where('pacientes.idClientes', $idClientes)
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->where('tipodeterminaciones.perfil', '>', 0)
            ->whereDate('pacientes.fechhoy', '>=', $desde)
            ->whereDate('pacientes.fechhoy', '<=', $hasta)
            ->count();

        return self::$cacheCantidad[$clave] = $cantidad;
    }

    public static function sumaDescuentosMesActual(int $idClientes, CarbonInterface $fechaReferencia): float
    {
        if (! self::tablasDisponibles() || ! Schema::hasColumn('determinaciones', 'descuento')) {
            return 0.0;
        }

        $inicioMes = $fechaReferencia->copy()->startOfMonth();
        $clave = "{$idClientes}:{$inicioMes->format('Y-m')}";

        if (array_key_exists($clave, self::$cacheDescuentos)) {
            return self::$cacheDescuentos[$clave];
        }

        $desde = $inicioMes->toDateString();
        $hasta = $inicioMes->copy()->endOfMonth()->toDateString();

        $suma = (float) Determinacion::query()
            ->join('pacientes', 'determinaciones.idPacientes', '=', 'pacientes.idPacientes')
            ->where('pacientes.idClientes', $idClientes)
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->whereDate('pacientes.fechhoy', '>=', $desde)
            ->whereDate('pacientes.fechhoy', '<=', $hasta)
            ->sum('determinaciones.descuento');

        return self::$cacheDescuentos[$clave] = round($suma, 2);
    }

    public static function porcentajePorCantidad(int $cantidadPerfiles): float
    {
        $porcentaje = 0.0;

        foreach (self::UMBRALES as $minimo => $pct) {
            if ($cantidadPerfiles >= $minimo) {
                $porcentaje = $pct;
            }
        }

        return $porcentaje;
    }

    /**
     * Próximo umbral a alcanzar con la cantidad de perfiles del mes en curso
     * (define el % del mes próximo). Null si ya está en el máximo.
     *
     * @return array{faltan: int, porcentaje: float, umbral: int}|null
     */
    public static function proximoUmbral(int $cantidadPerfilesMesActual): ?array
    {
        foreach (self::UMBRALES as $umbral => $porcentaje) {
            if ($cantidadPerfilesMesActual < $umbral) {
                return [
                    'faltan' => $umbral - $cantidadPerfilesMesActual,
                    'porcentaje' => $porcentaje,
                    'umbral' => $umbral,
                ];
            }
        }

        return null;
    }

    public static function formatearPorcentaje(float $porcentaje): string
    {
        return rtrim(rtrim(number_format($porcentaje, 1, '.', ''), '0'), '.').'%';
    }

    private static function tablasDisponibles(): bool
    {
        return Schema::hasTable('determinaciones')
            && Schema::hasTable('pacientes')
            && Schema::hasTable('tipodeterminaciones')
            && Schema::hasColumn('tipodeterminaciones', 'perfil');
    }
}
