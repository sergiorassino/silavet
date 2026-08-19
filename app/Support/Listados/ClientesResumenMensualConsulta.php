<?php

namespace App\Support\Listados;

use App\Models\Cliente;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listado «Clientes resumen mensual» (blank ScriptCase pacientesIVA).
 *
 * IVA: Precio con IVA = pacientes.neto (≥ 0); Neto s/IVA = round(neto / 1.21, 2);
 * IVA = round(neto − s/IVA, 2). El recuadro de descuento usa pacientes.precio.
 */
final class ClientesResumenMensualConsulta
{
    public const FACTOR_IVA = 1.21;

    /**
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return array{idClientes: int|null, fechaDesde: string, fechaHasta: string}
     */
    public static function normalizarFiltros(array $filtros): array
    {
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = now()->startOfMonth()->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = now()->toDateString();
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $idClientes = isset($filtros['idClientes']) && $filtros['idClientes'] !== null && $filtros['idClientes'] !== ''
            ? (int) $filtros['idClientes']
            : null;
        if ($idClientes !== null && $idClientes <= 0) {
            $idClientes = null;
        }

        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        return [
            'idClientes' => $idClientes,
            'fechaDesde' => $desde,
            'fechaHasta' => $hasta,
        ];
    }

    /**
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return LengthAwarePaginator<int, object>
     */
    public static function paginado(array $filtros, int $porPagina): LengthAwarePaginator
    {
        $paginator = self::queryBase($filtros)
            ->paginate($porPagina)
            ->through(fn (Paciente $paciente) => self::mapearFila($paciente));

        $paginator->setCollection(
            self::adjuntarDeterminaciones($paginator->getCollection())
        );

        return $paginator;
    }

    /**
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return Collection<int, object>
     */
    public static function listado(array $filtros): Collection
    {
        return self::adjuntarDeterminaciones(
            self::queryBase($filtros)
                ->get()
                ->map(fn (Paciente $paciente) => self::mapearFila($paciente))
        );
    }

    /**
     * Totales de todo el filtro (redondeo por fila, igual que el blank).
     *
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return array{
     *     cantidad: int,
     *     sum_sin_iva: float,
     *     sum_iva: float,
     *     sum_con_iva: float,
     *     sum_pagado: float,
     *     sum_cd_sin_iva: float,
     *     sum_cd_iva: float,
     *     sum_cd_con_iva: float
     * }
     */
    public static function totales(array $filtros): array
    {
        $filas = self::queryBase($filtros)
            ->get()
            ->map(fn (Paciente $paciente) => self::mapearFila($paciente));

        return self::acumular($filas);
    }

    /**
     * @param  iterable<object>  $filas
     * @return array{
     *     cantidad: int,
     *     sum_sin_iva: float,
     *     sum_iva: float,
     *     sum_con_iva: float,
     *     sum_pagado: float,
     *     sum_cd_sin_iva: float,
     *     sum_cd_iva: float,
     *     sum_cd_con_iva: float
     * }
     */
    public static function acumular(iterable $filas): array
    {
        $t = [
            'cantidad' => 0,
            'sum_sin_iva' => 0.0,
            'sum_iva' => 0.0,
            'sum_con_iva' => 0.0,
            'sum_pagado' => 0.0,
            'sum_cd_sin_iva' => 0.0,
            'sum_cd_iva' => 0.0,
            'sum_cd_con_iva' => 0.0,
        ];

        foreach ($filas as $fila) {
            $t['cantidad']++;
            $t['sum_sin_iva'] += (float) ($fila->sin_iva ?? 0);
            $t['sum_iva'] += (float) ($fila->iva ?? 0);
            $t['sum_con_iva'] += (float) ($fila->con_iva ?? 0);
            $t['sum_pagado'] += (float) ($fila->pagado ?? 0);
            $t['sum_cd_sin_iva'] += (float) ($fila->cd_sin_iva ?? 0);
            $t['sum_cd_iva'] += (float) ($fila->cd_iva ?? 0);
            $t['sum_cd_con_iva'] += (float) ($fila->cd_con_iva ?? 0);
        }

        foreach ($t as $k => $v) {
            if ($k === 'cantidad') {
                continue;
            }
            $t[$k] = round((float) $v, 2);
        }

        return $t;
    }

    /**
     * Encabezado de grupo, filas y subtotal (subtotal solo si hay más de un cliente).
     *
     * @param  iterable<object>  $filas
     * @return list<array{tipo: 'grupo'|'fila'|'subtotal', cliente?: string, fila?: object, cantidad?: int, sum_sin_iva?: float, sum_iva?: float, sum_con_iva?: float, sum_pagado?: float}>
     */
    public static function bloquesAgrupados(iterable $filas): array
    {
        $grupos = [];
        foreach ($filas as $fila) {
            $nombre = trim((string) ($fila->cliente ?? ''));
            if ($nombre === '') {
                $nombre = 'Sin cliente';
            }
            if (! isset($grupos[$nombre])) {
                $grupos[$nombre] = [
                    'cliente' => $nombre,
                    'filas' => [],
                    'cantidad' => 0,
                    'sum_sin_iva' => 0.0,
                    'sum_iva' => 0.0,
                    'sum_con_iva' => 0.0,
                    'sum_pagado' => 0.0,
                ];
            }
            $grupos[$nombre]['filas'][] = $fila;
            $grupos[$nombre]['cantidad']++;
            $grupos[$nombre]['sum_sin_iva'] += (float) ($fila->sin_iva ?? 0);
            $grupos[$nombre]['sum_iva'] += (float) ($fila->iva ?? 0);
            $grupos[$nombre]['sum_con_iva'] += (float) ($fila->con_iva ?? 0);
            $grupos[$nombre]['sum_pagado'] += (float) ($fila->pagado ?? 0);
        }

        $cantGrupos = count($grupos);
        $bloques = [];
        foreach ($grupos as $grupo) {
            $bloques[] = [
                'tipo' => 'grupo',
                'cliente' => $grupo['cliente'],
            ];
            foreach ($grupo['filas'] as $fila) {
                $bloques[] = ['tipo' => 'fila', 'fila' => $fila];
            }
            if ($cantGrupos > 1) {
                $bloques[] = [
                    'tipo' => 'subtotal',
                    'cliente' => $grupo['cliente'],
                    'cantidad' => $grupo['cantidad'],
                    'sum_sin_iva' => round($grupo['sum_sin_iva'], 2),
                    'sum_iva' => round($grupo['sum_iva'], 2),
                    'sum_con_iva' => round($grupo['sum_con_iva'], 2),
                    'sum_pagado' => round($grupo['sum_pagado'], 2),
                ];
            }
        }

        return $bloques;
    }

    /**
     * Recuadro verde: solo con un cliente filtrado y descuento > 0.
     *
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return array{mostrar: bool, nombre: string, pct: float, etiqueta: string}
     */
    public static function infoClienteFiltro(array $filtros): array
    {
        $filtros = self::normalizarFiltros($filtros);
        $id = $filtros['idClientes'];

        if ($id === null) {
            return [
                'mostrar' => false,
                'nombre' => 'Todos los clientes',
                'pct' => 0.0,
                'etiqueta' => '',
            ];
        }

        $cliente = Cliente::query()->find($id);
        $nombre = trim((string) ($cliente?->nombre ?? ''));
        if ($nombre === '') {
            $nombre = 'Cliente #'.$id;
        }
        $pct = 0.0;
        if ($cliente && Schema::hasColumn('clientes', 'descuento')) {
            $pct = round((float) ($cliente->descuento ?? 0), 2);
        }
        $etiqueta = '';
        if ($pct > 0) {
            $etiqueta = (floor($pct) == $pct)
                ? ((int) $pct).'% descuento'
                : number_format($pct, 2, ',', '.').'% descuento';
        }

        return [
            'mostrar' => $pct > 0,
            'nombre' => $nombre,
            'pct' => $pct,
            'etiqueta' => $etiqueta,
        ];
    }

    /**
     * @param  array{sin_iva: float, iva: float, con_iva: float}  $desglose
     */
    public static function desgloseIva(float $importeConIva): array
    {
        $conIva = $importeConIva < 0 ? 0.0 : $importeConIva;
        $sinIva = round($conIva / self::FACTOR_IVA, 2);
        $iva = round($conIva - $sinIva, 2);

        return [
            'sin_iva' => $sinIva,
            'iva' => $iva,
            'con_iva' => round($conIva, 2),
        ];
    }

    public static function formatearMoneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    public static function etiquetaPeriodo(string $desde, string $hasta): string
    {
        $n = self::normalizarFiltros(['fechaDesde' => $desde, 'fechaHasta' => $hasta]);

        return Carbon::parse($n['fechaDesde'])->format('d/m/Y')
            .' al '
            .Carbon::parse($n['fechaHasta'])->format('d/m/Y');
    }

    public static function mensajeVacio(array $filtros, array $infoCliente): string
    {
        $n = self::normalizarFiltros($filtros);
        $msg = 'No se encontraron pacientes';
        if ($n['idClientes'] !== null) {
            $msg .= ' del cliente '.$infoCliente['nombre'];
        }
        $msg .= ' entre '.self::etiquetaPeriodo($n['fechaDesde'], $n['fechaHasta']).'.';

        return $msg;
    }

    /**
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return Builder<Paciente>
     */
    private static function queryBase(array $filtros): Builder
    {
        $filtros = self::normalizarFiltros($filtros);

        $with = ['cliente:idClientes,nombre'];
        if (Schema::hasTable('mediodepago')) {
            $with[] = 'medioDePago:id,nombreMedioPago';
        }

        $query = Paciente::query()
            ->with($with)
            ->leftJoin('clientes as crm_cli', 'crm_cli.idClientes', '=', 'pacientes.idClientes')
            ->select('pacientes.*')
            ->where('pacientes.tipoRegistro', '<>', Paciente::TIPO_INGRESO)
            ->when($filtros['idClientes'] !== null, fn ($q) => $q->where('pacientes.idClientes', $filtros['idClientes']))
            ->whereDate('pacientes.fechhoy', '>=', $filtros['fechaDesde'])
            ->whereDate('pacientes.fechhoy', '<=', $filtros['fechaHasta'])
            ->orderByRaw("COALESCE(crm_cli.nombre, '') ASC")
            ->orderBy('pacientes.fechhoy')
            ->orderBy('pacientes.nombreProtocolo')
            ->orderBy('pacientes.nombre');

        return $query;
    }

    private static function mapearFila(Paciente $paciente): object
    {
        $neto = round((float) ($paciente->neto ?? 0), 2);
        $precio = round((float) ($paciente->precio ?? 0), 2);
        $ivaNeto = self::desgloseIva($neto);
        $ivaPrecio = self::desgloseIva($precio);
        $cliente = trim((string) ($paciente->cliente?->nombre ?? ''));
        $medio = trim((string) ($paciente->medioDePago?->nombreMedioPago ?? ''));

        return (object) [
            'idPacientes' => (int) $paciente->idPacientes,
            'idClientes' => (int) ($paciente->idClientes ?? 0),
            'cliente' => $cliente !== '' ? $cliente : 'Sin cliente',
            'fechhoy' => $paciente->fechhoy?->format('Y-m-d') ?? '',
            'nombreProtocolo' => trim((string) ($paciente->nombreProtocolo ?? '')),
            'nombre' => trim((string) ($paciente->nombre ?? '')),
            'pagado' => round((float) ($paciente->pagado ?? 0), 2),
            'mediodepago' => $medio !== '' ? $medio : 'Sin medio',
            'sin_iva' => $ivaNeto['sin_iva'],
            'iva' => $ivaNeto['iva'],
            'con_iva' => $ivaNeto['con_iva'],
            'cd_sin_iva' => $ivaPrecio['sin_iva'],
            'cd_iva' => $ivaPrecio['iva'],
            'cd_con_iva' => $ivaPrecio['con_iva'],
            'determinaciones' => [],
        ];
    }

    /**
     * @param  Collection<int, object>  $filas
     * @return Collection<int, object>
     */
    private static function adjuntarDeterminaciones(Collection $filas): Collection
    {
        if ($filas->isEmpty() || ! Schema::hasTable('determinaciones') || ! Schema::hasTable('tipodeterminaciones')) {
            return $filas;
        }

        $ids = $filas->pluck('idPacientes')->unique()->values()->all();
        if ($ids === []) {
            return $filas;
        }

        $query = DB::table('determinaciones as d')
            ->join('tipodeterminaciones as td', 'td.idTipodeterminaciones', '=', 'd.idTipodeterminaciones')
            ->whereIn('d.idPacientes', $ids);

        if (Schema::hasColumn('tipodeterminaciones', 'orden')) {
            $query->orderBy('td.orden');
        }

        $dets = $query
            ->orderBy('td.nombre')
            ->get(['d.idPacientes', 'td.nombre', 'd.precio']);

        $porPaciente = $dets->groupBy('idPacientes');

        return $filas->map(function (object $fila) use ($porPaciente) {
            $fila->determinaciones = $porPaciente
                ->get($fila->idPacientes, collect())
                ->map(fn (object $d) => (object) [
                    'nombre' => trim((string) ($d->nombre ?? '')),
                    'precio' => round((float) ($d->precio ?? 0), 2),
                ])
                ->values()
                ->all();

            return $fila;
        });
    }
}
