<?php

namespace App\Support\Listados;

use App\Models\Determinacion;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class DeterminacionesPorClienteConsulta
{
    /**
     * @param  array{
     *     idClientes?: int|null,
     *     busqueda?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return LengthAwarePaginator<int, object>
     */
    public static function gruposPaginados(array $filtros, int $porPagina, int $pagina = 1): LengthAwarePaginator
    {
        $grupos = self::grupos($filtros);
        $pagina = max(1, $pagina);
        $porPagina = max(1, $porPagina);

        return new Paginator(
            $grupos->forPage($pagina, $porPagina)->values(),
            $grupos->count(),
            $porPagina,
            $pagina,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     busqueda?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return Collection<int, object{
     *     idClientes: int,
     *     cliente: string,
     *     cantidad: int,
     *     sumaPrecio: float
     * }>
     */
    public static function grupos(array $filtros): Collection
    {
        if (! self::tablasDisponibles()) {
            return collect();
        }

        return self::queryBase($filtros)
            ->selectRaw('p.idClientes as idClientes')
            ->selectRaw("COALESCE(c.nombre, '') as cliente")
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('COALESCE(SUM(d.precio), 0) as sumaPrecio')
            ->groupBy('p.idClientes', 'c.nombre')
            ->orderByRaw("COALESCE(c.nombre, '') ASC")
            ->orderBy('p.idClientes')
            ->toBase()
            ->get()
            ->map(fn (object $fila) => (object) [
                'idClientes' => (int) ($fila->idClientes ?? 0),
                'cliente' => trim((string) ($fila->cliente ?? '')),
                'cantidad' => (int) ($fila->cantidad ?? 0),
                'sumaPrecio' => round((float) ($fila->sumaPrecio ?? 0), 2),
            ]);
    }

    /**
     * @param  list<int>  $idsClientes
     * @param  array{
     *     idClientes?: int|null,
     *     busqueda?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return array<int, list<object>>
     */
    public static function filasPorClientes(array $idsClientes, array $filtros): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $idsClientes),
            static fn (int $id): bool => $id >= 0,
        )));

        if ($ids === [] || ! self::tablasDisponibles()) {
            return [];
        }

        $filas = self::queryBase($filtros)
            ->where(function ($q) use ($ids) {
                $incluyeSinCliente = in_array(0, $ids, true);
                $idsPositivos = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

                if ($idsPositivos !== []) {
                    $q->whereIn('p.idClientes', $idsPositivos);
                }

                if ($incluyeSinCliente) {
                    $metodo = $idsPositivos !== [] ? 'orWhere' : 'where';
                    $q->{$metodo}(function ($inner) {
                        $inner->whereNull('p.idClientes')
                            ->orWhere('p.idClientes', 0);
                    });
                }
            })
            ->select([
                'd.idDeterminaciones',
                'd.precio',
                'p.idPacientes',
                'p.idClientes',
                'p.fechhoy',
                'p.nombreProtocolo',
                'p.nombre as nombrePaciente',
                'c.nombre as nombreCliente',
                'td.nombre as nombreDeterminacion',
            ])
            ->orderByRaw("COALESCE(c.nombre, '') ASC")
            ->orderBy('p.idClientes')
            ->orderByDesc('p.fechhoy')
            ->orderBy('p.nombreProtocolo')
            ->orderBy('td.nombre')
            ->orderBy('d.idDeterminaciones')
            ->toBase()
            ->get()
            ->map(fn (object $fila) => self::mapearFila($fila));

        $porCliente = [];
        foreach ($filas as $fila) {
            $porCliente[$fila->idClientes][] = $fila;
        }

        return $porCliente;
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     busqueda?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return Collection<int, object>
     */
    public static function listado(array $filtros): Collection
    {
        if (! self::tablasDisponibles()) {
            return collect();
        }

        return self::queryBase($filtros)
            ->select([
                'd.idDeterminaciones',
                'd.precio',
                'p.idPacientes',
                'p.idClientes',
                'p.fechhoy',
                'p.nombreProtocolo',
                'p.nombre as nombrePaciente',
                'c.nombre as nombreCliente',
                'td.nombre as nombreDeterminacion',
            ])
            ->orderByRaw("COALESCE(c.nombre, '') ASC")
            ->orderBy('p.idClientes')
            ->orderByDesc('p.fechhoy')
            ->orderBy('p.nombreProtocolo')
            ->orderBy('td.nombre')
            ->orderBy('d.idDeterminaciones')
            ->toBase()
            ->get()
            ->map(fn (object $fila) => self::mapearFila($fila));
    }

    /**
     * @param  iterable<object>  $filas
     * @return list<array{
     *     cliente: string,
     *     idClientes: int,
     *     cantidad: int,
     *     sumaPrecio: float,
     *     filas: list<object>
     * }>
     */
    public static function bloquesAgrupados(iterable $filas): array
    {
        $grupos = [];
        foreach ($filas as $fila) {
            $id = (int) ($fila->idClientes ?? 0);
            if (! isset($grupos[$id])) {
                $grupos[$id] = [
                    'cliente' => (string) ($fila->cliente ?? ''),
                    'idClientes' => $id,
                    'filas' => [],
                    'sumaPrecio' => 0.0,
                ];
            }
            $grupos[$id]['filas'][] = $fila;
            $grupos[$id]['sumaPrecio'] += round((float) ($fila->precio ?? 0), 2);
        }

        $bloques = [];
        foreach ($grupos as $grupo) {
            $bloques[] = [
                'cliente' => $grupo['cliente'],
                'idClientes' => $grupo['idClientes'],
                'cantidad' => count($grupo['filas']),
                'sumaPrecio' => round((float) $grupo['sumaPrecio'], 2),
                'filas' => $grupo['filas'],
            ];
        }

        return $bloques;
    }

    /**
     * @param  iterable<object>  $filas
     * @return array{cantidad: int, total_precio: float}
     */
    public static function resumen(iterable $filas): array
    {
        $totalPrecio = 0.0;
        $cantidad = 0;
        foreach ($filas as $fila) {
            $totalPrecio += round((float) ($fila->precio ?? 0), 2);
            $cantidad++;
        }

        return [
            'cantidad' => $cantidad,
            'total_precio' => round($totalPrecio, 2),
        ];
    }

    public static function formatearMoneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    public static function etiquetaPeriodo(?string $desde, ?string $hasta): string
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);

        if ($desde === '' && $hasta === '') {
            return 'Todo el historial';
        }

        $d = $desde !== '' ? Carbon::parse($desde)->format('d/m/Y') : 'Inicio';
        $h = $hasta !== '' ? Carbon::parse($hasta)->format('d/m/Y') : 'Hoy';

        return $d.' — '.$h;
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     busqueda?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return Builder<Determinacion>
     */
    private static function queryBase(array $filtros): Builder
    {
        $ctx = labCtx();
        $idClientes = isset($filtros['idClientes']) && $filtros['idClientes'] !== null && $filtros['idClientes'] !== ''
            ? (int) $filtros['idClientes']
            : null;
        $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));

        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        return Determinacion::query()
            ->from('determinaciones as d')
            ->join('pacientes as p', 'd.idPacientes', '=', 'p.idPacientes')
            ->leftJoin('clientes as c', 'p.idClientes', '=', 'c.idClientes')
            ->leftJoin('tipodeterminaciones as td', 'd.idTipodeterminaciones', '=', 'td.idTipodeterminaciones')
            ->where('p.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->when($idClientes !== null, fn ($q) => $q->where('p.idClientes', $idClientes))
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('p.idClientes', $ctx->idClientes))
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $q->where(function ($inner) use ($busqueda) {
                    $inner->where('c.nombre', 'like', '%'.$busqueda.'%')
                        ->orWhere('td.nombre', 'like', '%'.$busqueda.'%')
                        ->orWhere('p.nombreProtocolo', 'like', '%'.$busqueda.'%')
                        ->orWhere('p.nombre', 'like', '%'.$busqueda.'%');
                });
            })
            ->when($desde !== '', fn ($q) => $q->whereDate('p.fechhoy', '>=', Carbon::parse($desde)->toDateString()))
            ->when($hasta !== '', fn ($q) => $q->whereDate('p.fechhoy', '<=', Carbon::parse($hasta)->toDateString()));
    }

    private static function mapearFila(object $fila): object
    {
        $fechhoy = $fila->fechhoy ?? null;
        if ($fechhoy instanceof \DateTimeInterface) {
            $fecha = $fechhoy->format('Y-m-d');
        } else {
            $fecha = $fechhoy ? Carbon::parse((string) $fechhoy)->format('Y-m-d') : '';
        }

        return (object) [
            'idDeterminaciones' => (int) ($fila->idDeterminaciones ?? 0),
            'idPacientes' => (int) ($fila->idPacientes ?? 0),
            'idClientes' => (int) ($fila->idClientes ?? 0),
            'cliente' => trim((string) ($fila->nombreCliente ?? '')),
            'determinacion' => trim((string) ($fila->nombreDeterminacion ?? '')),
            'fechhoy' => $fecha,
            'protocolo' => trim((string) ($fila->nombreProtocolo ?? '')),
            'paciente' => trim((string) ($fila->nombrePaciente ?? '')),
            'precio' => round((float) ($fila->precio ?? 0), 2),
        ];
    }

    private static function tablasDisponibles(): bool
    {
        return Schema::hasTable('determinaciones')
            && Schema::hasTable('pacientes')
            && Schema::hasTable('clientes')
            && Schema::hasTable('tipodeterminaciones');
    }
}
