<?php

namespace App\Support\Resultados;

/**
 * Lee config('tenant.hemograma_auto'): flag + mapa rol → idItems del laboratorio.
 */
final class HemogramaAutoConfig
{
    /** @var list<string> */
    public const ROLES = [
        'hto',
        'eritrocitos',
        'hb',
        'vcm',
        'chcm',
        'plaquetas',
        'plaquetas_conteo_manual',
        'leucocitos',
        'neutrofilos',
        'bandas',
        'linfocitos',
        'eosinofilos',
        'basofilos',
        'monocitos',
        'serie_roja',
        'serie_blanca',
    ];

    public static function activo(): bool
    {
        return (bool) config('tenant.hemograma_auto.activo', false);
    }

    /**
     * Mapa rol → idItems (> 0). Roles sin id o null se omiten.
     *
     * @return array<string, int>
     */
    public static function items(): array
    {
        $raw = config('tenant.hemograma_auto.items', []);
        if (! is_array($raw)) {
            return [];
        }

        $mapa = [];
        foreach (self::ROLES as $rol) {
            if (! array_key_exists($rol, $raw)) {
                continue;
            }
            $id = (int) $raw[$rol];
            if ($id > 0) {
                $mapa[$rol] = $id;
            }
        }

        return $mapa;
    }

    /**
     * idItems de orígenes que deben disparar formulas() al cambiar.
     * Excluye solo los destinos de texto automático (serie_roja / serie_blanca).
     *
     * @return list<int>
     */
    public static function idItemsDisparo(): array
    {
        $items = self::items();
        $destinos = array_filter([
            $items['serie_roja'] ?? null,
            $items['serie_blanca'] ?? null,
        ]);

        $ids = [];
        foreach ($items as $id) {
            if (in_array($id, $destinos, true)) {
                continue;
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }
}
