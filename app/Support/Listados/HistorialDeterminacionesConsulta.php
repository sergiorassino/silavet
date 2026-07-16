<?php

namespace App\Support\Listados;

use App\Models\Paciente;
use App\Models\Renglon;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class HistorialDeterminacionesConsulta
{
    /** @var list<string> */
    public const OPERADORES_VALOR = ['=', '>', '>=', '<', '<=', 'entre'];

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     protocolo?: string,
     *     idGrupos?: int|null,
     *     idsItems?: list<int>,
     *     valorOperador?: string,
     *     valor?: string,
     *     valorHasta?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return LengthAwarePaginator<int, object>
     */
    public static function paginado(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return self::queryBase($filtros)
            ->paginate($porPagina)
            ->through(fn (object $fila) => self::mapearFila($fila));
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     protocolo?: string,
     *     idGrupos?: int|null,
     *     idsItems?: list<int>,
     *     valorOperador?: string,
     *     valor?: string,
     *     valorHasta?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return Collection<int, object>
     */
    public static function listado(array $filtros): Collection
    {
        return self::queryBase($filtros)
            ->get()
            ->map(fn (object $fila) => self::mapearFila($fila));
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
     * Expresión SQL que convierte `r.valor` (texto legacy) a número comparable.
     * Acepta: 8.8 · 3,216 · 6.310.000 · 1.234,56
     */
    public static function expresionValorNumerico(): string
    {
        return <<<'SQL'
CAST(
    CASE
        WHEN TRIM(r.valor) REGEXP '^[0-9]{1,3}(\\.[0-9]{3})+$'
            THEN REPLACE(TRIM(r.valor), '.', '')
        WHEN TRIM(r.valor) LIKE '%.%' AND TRIM(r.valor) LIKE '%,%'
            THEN REPLACE(REPLACE(TRIM(r.valor), '.', ''), ',', '.')
        WHEN TRIM(r.valor) LIKE '%,%'
            THEN REPLACE(TRIM(r.valor), ',', '.')
        ELSE TRIM(r.valor)
    END
AS DECIMAL(20, 6))
SQL;
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     protocolo?: string,
     *     idGrupos?: int|null,
     *     idsItems?: list<int>,
     *     valorOperador?: string,
     *     valor?: string,
     *     valorHasta?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string
     * }  $filtros
     * @return Builder<Renglon>
     */
    private static function queryBase(array $filtros): Builder
    {
        $ctx = labCtx();
        $idClientes = isset($filtros['idClientes']) && $filtros['idClientes'] !== null && $filtros['idClientes'] !== ''
            ? (int) $filtros['idClientes']
            : null;
        $paciente = trim((string) ($filtros['paciente'] ?? ''));
        $idEspecies = isset($filtros['idEspecies']) && $filtros['idEspecies'] !== null && $filtros['idEspecies'] !== ''
            ? (int) $filtros['idEspecies']
            : null;
        $protocolo = trim((string) ($filtros['protocolo'] ?? ''));
        $idGrupos = isset($filtros['idGrupos']) && $filtros['idGrupos'] !== null && $filtros['idGrupos'] !== ''
            ? (int) $filtros['idGrupos']
            : null;
        $idsItems = array_values(array_unique(array_filter(
            array_map('intval', (array) ($filtros['idsItems'] ?? [])),
            static fn (int $id): bool => $id > 0,
        )));
        $valorOperador = trim((string) ($filtros['valorOperador'] ?? ''));
        $valor = trim((string) ($filtros['valor'] ?? ''));
        $valorHasta = trim((string) ($filtros['valorHasta'] ?? ''));
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));

        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $query = Renglon::query()
            ->from('renglones as r')
            ->join('pacientes as p', 'r.idPacientes', '=', 'p.idPacientes')
            ->leftJoin('clientes as c', 'p.idClientes', '=', 'c.idClientes')
            ->leftJoin('especies as e', 'p.idEspecies', '=', 'e.idEspecies')
            ->leftJoin('grupos as g', 'r.idGrupos', '=', 'g.idGrupos')
            ->leftJoin('itemsinforme as i', 'r.idItems', '=', 'i.idItems')
            ->where('p.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->where('r.mostrar', 1)
            ->whereNotNull('r.valor')
            ->whereRaw("TRIM(r.valor) <> ''")
            ->whereIn('r.tipoItem', [1, 4, 7, 8, 9])
            ->when($idClientes !== null, fn ($q) => $q->where('p.idClientes', $idClientes))
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('p.idClientes', $ctx->idClientes))
            ->when($paciente !== '', function ($q) use ($paciente) {
                $q->where(function ($inner) use ($paciente) {
                    $inner->where('p.nombre', 'like', "%{$paciente}%")
                        ->orWhere('p.propietario', 'like', "%{$paciente}%");
                });
            })
            ->when($idEspecies !== null, fn ($q) => $q->where('p.idEspecies', $idEspecies))
            ->when($protocolo !== '', fn ($q) => $q->where('p.nombreProtocolo', 'like', "%{$protocolo}%"))
            ->when($idGrupos !== null, fn ($q) => $q->where('r.idGrupos', $idGrupos))
            ->when($idsItems !== [], fn ($q) => $q->whereIn('r.idItems', $idsItems))
            ->when($desde !== '', fn ($q) => $q->whereDate('p.fechhoy', '>=', Carbon::parse($desde)->toDateString()))
            ->when($hasta !== '', fn ($q) => $q->whereDate('p.fechhoy', '<=', Carbon::parse($hasta)->toDateString()));

        self::aplicarFiltroValor($query, $valorOperador, $valor, $valorHasta);

        return $query
            ->select([
                'r.idRenglones',
                'r.valor',
                'r.idItems',
                'r.idGrupos',
                'p.idPacientes',
                'p.idClientes',
                'p.fechhoy',
                'p.nombreProtocolo',
                'p.nombre as nombrePaciente',
                'c.nombre as nombreCliente',
                'e.nombre as nombreEspecie',
                'g.nombreGrupo',
                'i.nombreItem',
            ])
            ->orderByDesc('p.fechhoy')
            ->orderBy('p.nombreProtocolo')
            ->orderBy('g.orden')
            ->orderBy('g.idGrupos')
            ->orderBy('r.orden')
            ->orderBy('r.idRenglones');
    }

    /**
     * @param  Builder<Renglon>  $query
     */
    private static function aplicarFiltroValor(Builder $query, string $operador, string $valor, string $valorHasta): void
    {
        if ($operador === '' || $valor === '') {
            return;
        }

        if (! in_array($operador, self::OPERADORES_VALOR, true)) {
            return;
        }

        $numero = self::parsearNumeroFiltro($valor);
        if ($numero === null) {
            return;
        }

        $expr = self::expresionValorNumerico();

        if ($operador === 'entre') {
            $hasta = self::parsearNumeroFiltro($valorHasta);
            if ($hasta === null) {
                return;
            }
            $min = min($numero, $hasta);
            $max = max($numero, $hasta);
            $query->whereRaw("{$expr} BETWEEN ? AND ?", [$min, $max]);

            return;
        }

        $query->whereRaw("{$expr} {$operador} ?", [$numero]);
    }

    public static function parsearNumeroFiltro(string $texto): ?float
    {
        $v = trim($texto);
        if ($v === '') {
            return null;
        }

        $v = str_replace([' ', "\u{00A0}"], '', $v);

        if (str_contains($v, ',') && str_contains($v, '.')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif (str_contains($v, ',')) {
            $v = str_replace(',', '.', $v);
        } elseif (substr_count($v, '.') > 1) {
            $v = str_replace('.', '', $v);
        }

        if (! is_numeric($v)) {
            return null;
        }

        return (float) $v;
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
            'idRenglones' => (int) $fila->idRenglones,
            'idPacientes' => (int) $fila->idPacientes,
            'idClientes' => (int) ($fila->idClientes ?? 0),
            'idItems' => (int) ($fila->idItems ?? 0),
            'idGrupos' => (int) ($fila->idGrupos ?? 0),
            'fechhoy' => $fecha,
            'cliente' => trim((string) ($fila->nombreCliente ?? '')),
            'protocolo' => trim((string) ($fila->nombreProtocolo ?? '')),
            'paciente' => trim((string) ($fila->nombrePaciente ?? '')),
            'especie' => trim((string) ($fila->nombreEspecie ?? '')),
            'grupo' => trim((string) ($fila->nombreGrupo ?? '')),
            'determinacion' => trim((string) ($fila->nombreItem ?? '')),
            'valor' => trim((string) ($fila->valor ?? '')),
        ];
    }
}
