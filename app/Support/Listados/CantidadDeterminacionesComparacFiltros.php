<?php

namespace App\Support\Listados;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Validación común de filtros para cantidad determinaciones (comparac.).
 */
final class CantidadDeterminacionesComparacFiltros
{
    /**
     * @return array{
     *     idClientes: int|null,
     *     idsTipodeterminaciones: list<int>,
     *     periodo1Desde: string,
     *     periodo1Hasta: string,
     *     periodo2Desde: string,
     *     periodo2Hasta: string,
     *     orden: string
     * }
     */
    public static function desdeRequest(Request $request): array
    {
        $validated = $request->validate([
            'idClientes' => ['nullable', 'integer'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'periodo1Desde' => ['required', 'date'],
            'periodo1Hasta' => ['required', 'date', 'after_or_equal:periodo1Desde'],
            'periodo2Desde' => ['required', 'date'],
            'periodo2Hasta' => ['required', 'date', 'after_or_equal:periodo2Desde'],
            'orden' => ['nullable', 'string', Rule::in(CantidadDeterminacionesComparacConsulta::ORDENES)],
        ]);

        $ctx = labCtx();
        $idClientes = isset($validated['idClientes']) ? (int) $validated['idClientes'] : null;
        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) ($validated['ids'] ?? [])),
            static fn (int $id): bool => $id > 0,
        )));

        return [
            'idClientes' => $idClientes,
            'idsTipodeterminaciones' => $ids,
            'periodo1Desde' => trim((string) $validated['periodo1Desde']),
            'periodo1Hasta' => trim((string) $validated['periodo1Hasta']),
            'periodo2Desde' => trim((string) $validated['periodo2Desde']),
            'periodo2Hasta' => trim((string) $validated['periodo2Hasta']),
            'orden' => trim((string) ($validated['orden'] ?? 'nombre')) ?: 'nombre',
        ];
    }
}
