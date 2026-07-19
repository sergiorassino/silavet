<?php

namespace App\Support\Protocolos;

use App\Livewire\Protocolos\PacienteIndex;

/**
 * Conserva los filtros del listado de pacientes al ir a editar / determinaciones /
 * resultados y al volver (Cancelar, Guardar, Volver).
 *
 * Query params alineados con PacienteIndex: vista, filtroEstado, page.
 * Al volver también puede incluirse `foco` (idPacientes) para posicionar la fila.
 */
final class PacienteListadoFiltros
{
    /**
     * @return array{vista?: string, filtroEstado?: string, page?: int}
     */
    public static function desdeRequest(): array
    {
        return self::sanitizar([
            'vista' => request()->query('vista'),
            'filtroEstado' => request()->query('filtroEstado'),
            'page' => request()->query('page'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{vista?: string, filtroEstado?: string, page?: int}
     */
    public static function sanitizar(array $filtros): array
    {
        $out = [];

        $vista = trim((string) ($filtros['vista'] ?? ''));
        if (in_array($vista, [PacienteIndex::VISTA_HOY, PacienteIndex::VISTA_HISTORIAL], true)
            && $vista !== PacienteIndex::VISTA_HOY) {
            $out['vista'] = $vista;
        }

        $filtroEstado = trim((string) ($filtros['filtroEstado'] ?? ''));
        if (in_array($filtroEstado, [PacienteIndex::FILTRO_PENDIENTES, PacienteIndex::FILTRO_LISTOS], true)) {
            $out['filtroEstado'] = $filtroEstado;
        }

        $page = (int) ($filtros['page'] ?? 0);
        if ($page > 1) {
            $out['page'] = $page;
        }

        return $out;
    }

    /**
     * @param  array{vista?: string, filtroEstado?: string, page?: int}  $filtros
     */
    public static function urlIndex(array $filtros = [], ?int $focoIdPaciente = null): string
    {
        $params = $filtros !== [] ? $filtros : self::desdeRequest();

        if ($focoIdPaciente !== null && $focoIdPaciente > 0) {
            $params['foco'] = $focoIdPaciente;
        }

        return route('protocolos.index', $params);
    }
}
