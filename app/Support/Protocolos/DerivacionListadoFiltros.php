<?php

namespace App\Support\Protocolos;

use App\Livewire\Protocolos\DerivacionIndex;

/**
 * Conserva agrupación / finalizados / página del listado de derivaciones al ir
 * a cargar resultados (o editar el protocolo) y al volver.
 *
 * Al volver también puede incluirse `foco` (idPacientes) para posicionar la fila.
 */
final class DerivacionListadoFiltros
{
    /**
     * @return array{agrupacion?: string, incluirFinalizados?: int, page?: int}
     */
    public static function desdeRequest(): array
    {
        return self::sanitizar([
            'agrupacion' => request()->query('agrupacion'),
            'incluirFinalizados' => request()->query('incluirFinalizados'),
            'page' => request()->query('page'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{agrupacion?: string, incluirFinalizados?: int, page?: int}
     */
    public static function sanitizar(array $filtros): array
    {
        $out = [];

        $agrupacion = trim((string) ($filtros['agrupacion'] ?? ''));
        if (in_array($agrupacion, [
            DerivacionIndex::AGRUPACION_NINGUNA,
            DerivacionIndex::AGRUPACION_CLIENTE,
            DerivacionIndex::AGRUPACION_FECHA,
        ], true)) {
            $out['agrupacion'] = $agrupacion;
        }

        $incluir = $filtros['incluirFinalizados'] ?? false;
        if ($incluir === true || $incluir === 1 || $incluir === '1' || $incluir === 'true') {
            $out['incluirFinalizados'] = 1;
        }

        $page = (int) ($filtros['page'] ?? 0);
        if ($page > 1) {
            $out['page'] = $page;
        }

        return $out;
    }

    /**
     * @param  array{agrupacion?: string, incluirFinalizados?: int, page?: int}  $filtros
     */
    public static function urlIndex(array $filtros = [], ?int $focoIdPaciente = null): string
    {
        $params = $filtros !== [] ? $filtros : self::desdeRequest();

        if ($focoIdPaciente !== null && $focoIdPaciente > 0) {
            $params['foco'] = $focoIdPaciente;
        }

        return route('derivaciones.index', $params);
    }
}
