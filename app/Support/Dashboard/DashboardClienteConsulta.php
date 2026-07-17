<?php

namespace App\Support\Dashboard;

use App\Models\Notificacion;
use App\Models\Paciente;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\Security\OpaqueRouteToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas y listados del panel de autogestión del cliente veterinario.
 */
final class DashboardClienteConsulta
{
    public const LIMITE_INFORMES = 8;

    public const LIMITE_AVISOS = 8;

    /** Listado completo del panel de la campana (mensajes sin leer). */
    public const LIMITE_AVISOS_PANEL = 100;

    public const LIMITE_ACTIVIDAD = 8;

    public const LIMITE_MOVIMIENTOS_CC = 5;

    /**
     * @return array{
     *     fecha: string,
     *     fechaFormato: string,
     *     usaEstadoFinalEnv: bool,
     *     hoy: array{
     *         total: int,
     *         porEstado: array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>,
     *         conic: string
     *     },
     *     semana: array{
     *         total: int,
     *         porEstado: array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>,
     *         desde: string,
     *         hasta: string
     *     },
     *     pendientesResultado: int,
     *     informesListos: int,
     *     puedeVerInformes: bool,
     *     ultimosInformes: list<array{
     *         idPacientes: int,
     *         protocolo: string,
     *         nombre: string,
     *         tutor: string,
     *         fecha: string,
     *         estado: string,
     *         esNuevo: bool,
     *         urlInforme: string|null
     *     }>,
     *     avisosNoLeidos: int,
     *     avisos: list<array{
     *         id: int,
     *         idPacientes: int|null,
     *         protocolo: string,
     *         nombre: string,
     *         texto: string,
     *         fecha: string,
     *         urlInforme: string|null
     *     }>,
     *     cuentaCorriente: array{
     *         saldo: float,
     *         saldoFormateado: string,
     *         pendientes: list<array{
     *             idPacientes: int,
     *             protocolo: string,
     *             nombre: string,
     *             fecha: string,
     *             saldoPendiente: float,
     *             saldoPendienteFormateado: string
     *         }>
     *     },
     *     actividadReciente: list<array{
     *         idPacientes: int,
     *         protocolo: string,
     *         nombre: string,
     *         tutor: string,
     *         fecha: string,
     *         estado: string,
     *         precioFormateado: string,
     *         urlInforme: string|null
     *     }>
     * }
     */
    public static function metricas(int $idClientes, bool $puedeVerInformes = true): array
    {
        $hoy = now()->startOfDay();
        $fechaSql = $hoy->toDateString();
        $inicioSemana = $hoy->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $usaFinalEnv = ResultadosEstadosCatalog::usaFinalEnv();

        $porEstadoHoy = self::conteoEstados($idClientes, $fechaSql, $fechaSql);
        $totalHoy = array_sum(array_column($porEstadoHoy, 'cantidad'));
        self::aplicarPorcentajes($porEstadoHoy, $totalHoy);

        $porEstadoSemana = self::conteoEstados($idClientes, $inicioSemana, $fechaSql);
        $totalSemana = array_sum(array_column($porEstadoSemana, 'cantidad'));
        self::aplicarPorcentajes($porEstadoSemana, $totalSemana);

        $estadosPendientes = [ResultadosEstadosCatalog::EN_PROC, ResultadosEstadosCatalog::PARCIAL];
        $estadosListos = ResultadosEstadosCatalog::estadosFinalizados();

        return [
            'fecha' => $fechaSql,
            'fechaFormato' => $hoy->format('d/m/Y'),
            'usaEstadoFinalEnv' => $usaFinalEnv,
            'hoy' => [
                'total' => $totalHoy,
                'porEstado' => $porEstadoHoy,
                'conic' => self::conicGradient($porEstadoHoy, $totalHoy),
            ],
            'semana' => [
                'total' => $totalSemana,
                'porEstado' => $porEstadoSemana,
                'desde' => Carbon::parse($inicioSemana)->format('d/m/Y'),
                'hasta' => $hoy->format('d/m/Y'),
            ],
            'pendientesResultado' => self::conteoPorEstadosAbiertos($idClientes, $estadosPendientes),
            'informesListos' => self::conteoPorEstadosAbiertos($idClientes, $estadosListos, soloHoy: true),
            'puedeVerInformes' => $puedeVerInformes,
            'ultimosInformes' => self::ultimosInformes($idClientes, $puedeVerInformes),
            'avisosNoLeidos' => self::conteoAvisosNoLeidos($idClientes),
            'avisos' => self::avisosNoLeidos($idClientes, $puedeVerInformes),
            'cuentaCorriente' => self::resumenCuentaCorriente($idClientes),
            'actividadReciente' => self::actividadReciente($idClientes, $puedeVerInformes),
        ];
    }

    /**
     * @return array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>
     */
    private static function conteoEstados(int $idClientes, string $desde, string $hasta): array
    {
        $definiciones = [
            ResultadosEstadosCatalog::EN_PROC => 'En proceso',
            ResultadosEstadosCatalog::PARCIAL => 'Parcial',
            ResultadosEstadosCatalog::FINAL => 'Final',
            ResultadosEstadosCatalog::FINAL_ENV => 'Final/Env',
        ];

        $mapa = [];
        foreach (ResultadosEstadosCatalog::valores() as $estado) {
            $mapa[$estado] = [
                'etiqueta' => $definiciones[$estado],
                'cantidad' => 0,
                'porcentaje' => 0.0,
                'color' => ResultadosEstadosCatalog::colorDashboard($estado),
            ];
        }

        $filas = self::queryCasos($idClientes)
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->whereDate('fechhoy', '>=', $desde)
            ->whereDate('fechhoy', '<=', $hasta)
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
     * @param  list<string>  $estados
     */
    private static function conteoPorEstadosAbiertos(int $idClientes, array $estados, bool $soloHoy = false): int
    {
        return (int) self::queryCasos($idClientes)
            ->where(function ($q) use ($estados) {
                if (in_array(ResultadosEstadosCatalog::EN_PROC, $estados, true)) {
                    $q->where(function ($inner) use ($estados) {
                        $inner->whereIn('estado', $estados)
                            ->orWhereNull('estado')
                            ->orWhere('estado', '');
                    });
                } else {
                    $q->whereIn('estado', $estados);
                }
            })
            ->when($soloHoy, fn ($q) => $q->whereDate('fechhoy', now()->toDateString()))
            ->count();
    }

    /**
     * @return list<array{
     *     idPacientes: int,
     *     protocolo: string,
     *     nombre: string,
     *     tutor: string,
     *     fecha: string,
     *     estado: string,
     *     esNuevo: bool,
     *     urlInforme: string|null
     * }>
     */
    private static function ultimosInformes(int $idClientes, bool $puedeVerInformes): array
    {
        $idsConAvisoNuevo = self::idsPacientesConAvisoNoLeido($idClientes);
        $estadosListos = ResultadosEstadosCatalog::estadosFinalizados();
        $hoy = now()->toDateString();

        return self::queryCasos($idClientes)
            ->whereIn('estado', $estadosListos)
            ->orderByDesc('fechhoy')
            ->orderByDesc('idPacientes')
            ->limit(self::LIMITE_INFORMES)
            ->get(['idPacientes', 'nombreProtocolo', 'nombre', 'propietario', 'fechhoy', 'estado'])
            ->map(function (Paciente $p) use ($puedeVerInformes, $idsConAvisoNuevo, $hoy) {
                $id = (int) $p->idPacientes;
                $fecha = $p->fechhoy?->format('Y-m-d') ?? '';

                return [
                    'idPacientes' => $id,
                    'protocolo' => trim((string) ($p->nombreProtocolo ?? '')) ?: '—',
                    'nombre' => trim((string) ($p->nombre ?? '')) ?: '—',
                    'tutor' => trim((string) ($p->propietario ?? '')) ?: '—',
                    'fecha' => $p->fechhoyFormateada(),
                    'estado' => ResultadosEstadosCatalog::normalizar($p->estado),
                    'esNuevo' => isset($idsConAvisoNuevo[$id]) || $fecha === $hoy,
                    'urlInforme' => $puedeVerInformes
                        ? route('cliente.pacientes.informe', [
                            'ref' => OpaqueRouteToken::forInformePaciente($id),
                        ])
                        : null,
                ];
            })
            ->all();
    }

    public static function conteoAvisosNoLeidos(int $idClientes): int
    {
        if (! Schema::hasTable('notificaciones')) {
            return 0;
        }

        return (int) Notificacion::query()
            ->where('idClientes', $idClientes)
            ->where(function ($q) {
                $q->whereNull('leido')->orWhere('leido', 0);
            })
            ->whereNotNull('notificacion')
            ->where('notificacion', '!=', '')
            ->count();
    }

    /**
     * @return list<array{
     *     id: int,
     *     idPacientes: int|null,
     *     protocolo: string,
     *     nombre: string,
     *     texto: string,
     *     fecha: string,
     *     urlInforme: string|null
     * }>
     */
    public static function avisosNoLeidos(
        int $idClientes,
        bool $puedeVerInformes,
        ?int $limite = null,
    ): array {
        if (! Schema::hasTable('notificaciones')) {
            return [];
        }

        $limite = $limite ?? self::LIMITE_AVISOS;

        return Notificacion::query()
            ->with(['paciente:idPacientes,nombreProtocolo,nombre'])
            ->where('idClientes', $idClientes)
            ->where(function ($q) {
                $q->whereNull('leido')->orWhere('leido', 0);
            })
            ->whereNotNull('notificacion')
            ->where('notificacion', '!=', '')
            ->orderByDesc('fechaCreacion')
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(function (Notificacion $n) use ($puedeVerInformes) {
                $idPac = $n->idPacientes !== null ? (int) $n->idPacientes : null;
                $paciente = $n->paciente;

                return [
                    'id' => (int) $n->id,
                    'idPacientes' => $idPac,
                    'protocolo' => trim((string) ($paciente?->nombreProtocolo ?? '')) ?: '—',
                    'nombre' => trim((string) ($paciente?->nombre ?? '')) ?: '—',
                    'texto' => self::textoAvisoPlano((string) ($n->notificacion ?? '')),
                    'fecha' => $n->fechaCreacion?->format('d/m/Y H:i') ?? '—',
                    'urlInforme' => ($puedeVerInformes && $idPac)
                        ? route('cliente.pacientes.informe', [
                            'ref' => OpaqueRouteToken::forInformePaciente($idPac),
                        ])
                        : null,
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *     saldo: float,
     *     saldoFormateado: string,
     *     pendientes: list<array{
     *         idPacientes: int,
     *         protocolo: string,
     *         nombre: string,
     *         fecha: string,
     *         saldoPendiente: float,
     *         saldoPendienteFormateado: string
     *     }>
     * }
     */
    private static function resumenCuentaCorriente(int $idClientes): array
    {
        $saldo = CuentaCorrienteConsulta::saldoClienteHoy($idClientes);

        $pendientes = Paciente::query()
            ->where('idClientes', $idClientes)
            ->whereRaw('COALESCE(tipoRegistro, 0) NOT IN (?, ?)', [
                Paciente::TIPO_INGRESO,
                Paciente::TIPO_EGRESO,
            ])
            ->whereRaw('(COALESCE(precio, 0) - COALESCE(pagado, 0)) > 0.009')
            ->orderByDesc('fechhoy')
            ->orderByDesc('idPacientes')
            ->limit(self::LIMITE_MOVIMIENTOS_CC)
            ->get(['idPacientes', 'nombreProtocolo', 'nombre', 'fechhoy', 'precio', 'pagado', 'tipoRegistro'])
            ->map(function (Paciente $p) {
                $saldoPendiente = CuentaCorrienteConsulta::movimientoNetoProtocolo($p);

                return [
                    'idPacientes' => (int) $p->idPacientes,
                    'protocolo' => trim((string) ($p->nombreProtocolo ?? '')) ?: '—',
                    'nombre' => trim((string) ($p->nombre ?? '')) ?: '—',
                    'fecha' => $p->fechhoyFormateada(),
                    'saldoPendiente' => $saldoPendiente,
                    'saldoPendienteFormateado' => CuentaCorrienteConsulta::formatearMoneda($saldoPendiente),
                ];
            })
            ->all();

        return [
            'saldo' => $saldo,
            'saldoFormateado' => CuentaCorrienteConsulta::formatearMoneda($saldo),
            'pendientes' => $pendientes,
        ];
    }

    /**
     * @return list<array{
     *     idPacientes: int,
     *     protocolo: string,
     *     nombre: string,
     *     tutor: string,
     *     fecha: string,
     *     estado: string,
     *     precioFormateado: string,
     *     urlInforme: string|null
     * }>
     */
    private static function actividadReciente(int $idClientes, bool $puedeVerInformes): array
    {
        $estadosListos = ResultadosEstadosCatalog::estadosFinalizados();

        return self::queryCasos($idClientes)
            ->orderByDesc('fechhoy')
            ->orderByDesc('idPacientes')
            ->limit(self::LIMITE_ACTIVIDAD)
            ->get(['idPacientes', 'nombreProtocolo', 'nombre', 'propietario', 'fechhoy', 'estado', 'precio', 'descuento', 'neto'])
            ->map(function (Paciente $p) use ($puedeVerInformes, $estadosListos) {
                $id = (int) $p->idPacientes;
                $estado = ResultadosEstadosCatalog::normalizar($p->estado);
                $listo = in_array($estado, $estadosListos, true);

                return [
                    'idPacientes' => $id,
                    'protocolo' => trim((string) ($p->nombreProtocolo ?? '')) ?: '—',
                    'nombre' => trim((string) ($p->nombre ?? '')) ?: '—',
                    'tutor' => trim((string) ($p->propietario ?? '')) ?: '—',
                    'fecha' => $p->fechhoyFormateada(),
                    'estado' => $estado,
                    'precioFormateado' => $p->precioConDescuentoFormateado(),
                    'urlInforme' => ($puedeVerInformes && $listo)
                        ? route('cliente.pacientes.informe', [
                            'ref' => OpaqueRouteToken::forInformePaciente($id),
                        ])
                        : null,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, true>
     */
    private static function idsPacientesConAvisoNoLeido(int $idClientes): array
    {
        if (! Schema::hasTable('notificaciones')) {
            return [];
        }

        return Notificacion::query()
            ->where('idClientes', $idClientes)
            ->where(function ($q) {
                $q->whereNull('leido')->orWhere('leido', 0);
            })
            ->whereNotNull('idPacientes')
            ->pluck('idPacientes')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Paciente>
     */
    private static function queryCasos(int $idClientes)
    {
        return Paciente::query()
            ->where('idClientes', $idClientes)
            ->whereRaw('COALESCE(tipoRegistro, 0) NOT IN (?, ?)', [
                Paciente::TIPO_INGRESO,
                Paciente::TIPO_EGRESO,
            ]);
    }

    /**
     * @param  array<string, array{etiqueta: string, cantidad: int, porcentaje: float, color: string}>  $porEstado
     */
    private static function aplicarPorcentajes(array &$porEstado, int $total): void
    {
        foreach ($porEstado as $clave => $fila) {
            $porEstado[$clave]['porcentaje'] = $total > 0
                ? round(($fila['cantidad'] / $total) * 100, 1)
                : 0.0;
        }
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

    public static function textoAvisoPlano(string $html): string
    {
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    public static function marcarAvisoLeido(int $idClientes, int $idNotificacion): bool
    {
        if (! Schema::hasTable('notificaciones')) {
            return false;
        }

        $aviso = Notificacion::query()
            ->where('id', $idNotificacion)
            ->where('idClientes', $idClientes)
            ->first();

        if ($aviso === null) {
            return false;
        }

        $aviso->update(['leido' => 1]);

        return true;
    }

    public static function marcarTodosAvisosLeidos(int $idClientes): int
    {
        if (! Schema::hasTable('notificaciones')) {
            return 0;
        }

        return Notificacion::query()
            ->where('idClientes', $idClientes)
            ->where(function ($q) {
                $q->whereNull('leido')->orWhere('leido', 0);
            })
            ->update(['leido' => 1]);
    }
}
