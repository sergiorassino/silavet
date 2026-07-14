<?php

namespace App\Support\Listados;

use App\Models\Determinacion;
use App\Models\Paciente;
use App\Models\Tipodeterminacion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class CantidadDeterminacionesComparacConsulta
{
    /** @var list<string> */
    public const ORDENES = [
        'nombre',
        'periodo1_desc',
        'periodo1_asc',
        'periodo2_desc',
        'periodo2_asc',
        'diferencia_desc',
        'diferencia_asc',
    ];

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     idsTipodeterminaciones?: list<int>,
     *     periodo1Desde?: string,
     *     periodo1Hasta?: string,
     *     periodo2Desde?: string,
     *     periodo2Hasta?: string,
     *     orden?: string
     * }  $filtros
     * @return Collection<int, object{
     *     idTipodeterminaciones: int,
     *     nombre: string,
     *     cantidad1: int,
     *     cantidad2: int,
     *     diferencia: int
     * }>
     */
    public static function comparativa(array $filtros): Collection
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) ($filtros['idsTipodeterminaciones'] ?? [])),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === [] || ! Schema::hasTable('determinaciones') || ! Schema::hasTable('tipodeterminaciones')) {
            return collect();
        }

        $tipos = Tipodeterminacion::query()
            ->whereIn('idTipodeterminaciones', $ids)
            ->orderBy('nombre')
            ->get(['idTipodeterminaciones', 'nombre'])
            ->keyBy('idTipodeterminaciones');

        $conteos1 = self::conteosPorTipo(
            $ids,
            $filtros['idClientes'] ?? null,
            (string) ($filtros['periodo1Desde'] ?? ''),
            (string) ($filtros['periodo1Hasta'] ?? ''),
        );
        $conteos2 = self::conteosPorTipo(
            $ids,
            $filtros['idClientes'] ?? null,
            (string) ($filtros['periodo2Desde'] ?? ''),
            (string) ($filtros['periodo2Hasta'] ?? ''),
        );

        $filas = collect();
        foreach ($ids as $idTipo) {
            $tipo = $tipos->get($idTipo);
            if ($tipo === null) {
                continue;
            }

            $c1 = (int) ($conteos1[$idTipo] ?? 0);
            $c2 = (int) ($conteos2[$idTipo] ?? 0);

            $filas->push((object) [
                'idTipodeterminaciones' => $idTipo,
                'nombre' => (string) $tipo->nombre,
                'cantidad1' => $c1,
                'cantidad2' => $c2,
                'diferencia' => $c2 - $c1,
            ]);
        }

        return self::ordenar($filas, (string) ($filtros['orden'] ?? 'nombre'));
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    private static function conteosPorTipo(array $ids, ?int $idClientes, string $desde, string $hasta): array
    {
        $desde = trim($desde);
        $hasta = trim($hasta);
        if ($desde === '' || $hasta === '') {
            return [];
        }

        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $query = Determinacion::query()
            ->from('determinaciones as d')
            ->join('pacientes as p', 'p.idPacientes', '=', 'd.idPacientes')
            ->where('p.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->whereIn('d.idTipodeterminaciones', $ids)
            ->whereDate('p.fechhoy', '>=', Carbon::parse($desde)->toDateString())
            ->whereDate('p.fechhoy', '<=', Carbon::parse($hasta)->toDateString())
            ->when($idClientes, fn ($q) => $q->where('p.idClientes', $idClientes))
            ->selectRaw('d.idTipodeterminaciones as id_tipo, COUNT(*) as cantidad')
            ->groupBy('d.idTipodeterminaciones');

        $out = [];
        foreach ($query->get() as $fila) {
            $out[(int) $fila->id_tipo] = (int) $fila->cantidad;
        }

        return $out;
    }

    /**
     * @param  Collection<int, object>  $filas
     * @return Collection<int, object>
     */
    private static function ordenar(Collection $filas, string $orden): Collection
    {
        $orden = in_array($orden, self::ORDENES, true) ? $orden : 'nombre';

        return match ($orden) {
            'periodo1_desc' => $filas->sortByDesc('cantidad1')->values(),
            'periodo1_asc' => $filas->sortBy('cantidad1')->values(),
            'periodo2_desc' => $filas->sortByDesc('cantidad2')->values(),
            'periodo2_asc' => $filas->sortBy('cantidad2')->values(),
            'diferencia_desc' => $filas->sortByDesc('diferencia')->values(),
            'diferencia_asc' => $filas->sortBy('diferencia')->values(),
            default => $filas->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };
    }

    public static function etiquetaPeriodo(?string $desde, ?string $hasta): string
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);

        if ($desde === '' || $hasta === '') {
            return '—';
        }

        return Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y');
    }

    public static function etiquetaPeriodoCorta(?string $desde, ?string $hasta): string
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);

        if ($desde === '' || $hasta === '') {
            return '—';
        }

        return Carbon::parse($desde)->format('d-m-Y').' / '.Carbon::parse($hasta)->format('d-m-Y');
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{
     *     total1: int,
     *     total2: int,
     *     totalDiferencia: int,
     *     periodo1: string,
     *     periodo2: string,
     *     periodo1Corto: string,
     *     periodo2Corto: string
     * }
     */
    public static function resumen(Collection $filas, array $filtros): array
    {
        $total1 = (int) $filas->sum('cantidad1');
        $total2 = (int) $filas->sum('cantidad2');

        return [
            'total1' => $total1,
            'total2' => $total2,
            'totalDiferencia' => $total2 - $total1,
            'periodo1' => self::etiquetaPeriodo(
                (string) ($filtros['periodo1Desde'] ?? ''),
                (string) ($filtros['periodo1Hasta'] ?? ''),
            ),
            'periodo2' => self::etiquetaPeriodo(
                (string) ($filtros['periodo2Desde'] ?? ''),
                (string) ($filtros['periodo2Hasta'] ?? ''),
            ),
            'periodo1Corto' => self::etiquetaPeriodoCorta(
                (string) ($filtros['periodo1Desde'] ?? ''),
                (string) ($filtros['periodo1Hasta'] ?? ''),
            ),
            'periodo2Corto' => self::etiquetaPeriodoCorta(
                (string) ($filtros['periodo2Desde'] ?? ''),
                (string) ($filtros['periodo2Hasta'] ?? ''),
            ),
        ];
    }
}
