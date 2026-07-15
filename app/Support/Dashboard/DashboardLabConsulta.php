<?php

namespace App\Support\Dashboard;

use App\Models\Determinacion;
use App\Models\Paciente;
use App\Support\Resultados\ResultadosEstadosCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas operativas del panel de laboratorio.
 */
final class DashboardLabConsulta
{
    /**
     * @return array{
     *     fecha: string,
     *     fechaFormato: string,
     *     hoy: array{
     *         total: int,
     *         porEstado: array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>,
     *         conic: string
     *     },
     *     derivacionesPendientes: int,
     *     sinFinal: int,
     *     sinFinalEnv: int|null,
     *     usaEstadoFinalEnv: bool
     * }
     */
    public static function metricas(?Carbon $fecha = null): array
    {
        $fecha = ($fecha ?? now())->startOfDay();
        $fechaSql = $fecha->toDateString();
        $usaFinalEnv = ResultadosEstadosCatalog::usaFinalEnv();

        $porEstado = self::conteoEstadosHoy($fechaSql);
        $totalHoy = array_sum(array_column($porEstado, 'cantidad'));

        foreach ($porEstado as $clave => $fila) {
            $porEstado[$clave]['porcentaje'] = $totalHoy > 0
                ? round(($fila['cantidad'] / $totalHoy) * 100, 1)
                : 0.0;
        }

        return [
            'fecha' => $fechaSql,
            'fechaFormato' => $fecha->format('d/m/Y'),
            'hoy' => [
                'total' => $totalHoy,
                'porEstado' => $porEstado,
                'conic' => self::conicGradient($porEstado, $totalHoy),
            ],
            'derivacionesPendientes' => self::conteoDerivacionesPendientes(),
            'sinFinal' => self::conteoSinAlcanzarEstados(ResultadosEstadosCatalog::estadosFinalizados()),
            'sinFinalEnv' => $usaFinalEnv
                ? self::conteoSinAlcanzarEstados([ResultadosEstadosCatalog::FINAL_ENV])
                : null,
            'usaEstadoFinalEnv' => $usaFinalEnv,
        ];
    }

    /**
     * @return array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>
     */
    private static function conteoEstadosHoy(string $fechaSql): array
    {
        $definiciones = [
            ResultadosEstadosCatalog::EN_PROC => [
                'etiqueta' => 'En proceso',
            ],
            ResultadosEstadosCatalog::PARCIAL => [
                'etiqueta' => 'Parcial',
            ],
            ResultadosEstadosCatalog::FINAL => [
                'etiqueta' => 'Final',
            ],
            ResultadosEstadosCatalog::FINAL_ENV => [
                'etiqueta' => 'Final/Env',
            ],
        ];

        $mapa = [];
        foreach (ResultadosEstadosCatalog::valores() as $estado) {
            $mapa[$estado] = [
                'etiqueta' => $definiciones[$estado]['etiqueta'],
                'cantidad' => 0,
                'porcentaje' => 0.0,
                'color' => ResultadosEstadosCatalog::colorDashboard($estado),
            ];
        }

        $filas = Paciente::query()
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->where('tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->whereDate('fechhoy', $fechaSql)
            ->groupBy('estado')
            ->get();

        foreach ($filas as $fila) {
            $estado = ResultadosEstadosCatalog::normalizar($fila->estado);
            if (! isset($mapa[$estado])) {
                continue;
            }
            $mapa[$estado]['cantidad'] += (int) $fila->cantidad;
        }

        return $mapa;
    }

    /**
     * Determinaciones derivadas en protocolos que no alcanzaron Final ni Final/Env.
     */
    private static function conteoDerivacionesPendientes(): int
    {
        if (! Schema::hasTable('determinaciones')) {
            return 0;
        }

        return (int) Determinacion::query()
            ->where('determinaciones.idDerivaciones', '>', 0)
            ->join('pacientes', 'pacientes.idPacientes', '=', 'determinaciones.idPacientes')
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->whereNotIn('pacientes.estado', ResultadosEstadosCatalog::estadosFinalizados())
            ->count('determinaciones.idDeterminaciones');
    }

    /**
     * Protocolos que aún no alcanzaron ninguno de los estados indicados.
     *
     * @param  list<string>  $estadosAlcanzados
     */
    private static function conteoSinAlcanzarEstados(array $estadosAlcanzados): int
    {
        return (int) Paciente::query()
            ->where('tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->where(function ($q) use ($estadosAlcanzados) {
                $q->whereNull('estado')
                    ->orWhere('estado', '')
                    ->orWhereNotIn('estado', $estadosAlcanzados);
            })
            ->count();
    }

    /**
     * @param  array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>  $porEstado
     */
    private static function conicGradient(array $porEstado, int $total): string
    {
        if ($total <= 0) {
            return 'conic-gradient(#e2e8f0 0% 100%)';
        }

        $partes = [];
        $acumulado = 0.0;

        foreach ($porEstado as $fila) {
            if ($fila['cantidad'] <= 0) {
                continue;
            }
            $porcentaje = ($fila['cantidad'] / $total) * 100;
            $desde = $acumulado;
            $hasta = $acumulado + $porcentaje;
            $partes[] = sprintf('%s %.4f%% %.4f%%', $fila['color'], $desde, $hasta);
            $acumulado = $hasta;
        }

        if ($partes === []) {
            return 'conic-gradient(#e2e8f0 0% 100%)';
        }

        return 'conic-gradient('.implode(', ', $partes).')';
    }
}
