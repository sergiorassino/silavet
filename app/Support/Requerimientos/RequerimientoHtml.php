<?php

namespace App\Support\Requerimientos;

/**
 * Sanitiza HTML legacy de requerimientos (títulos/colores/listas) para Blade.
 */
class RequerimientoHtml
{
    private const ETIQUETAS_PERMITIDAS = '<b><i><u><strong><em><br><br/><p><ul><ol><li><div><span><font><hr>';

    public static function sanitizar(string $html): string
    {
        return strip_tags($html, self::ETIQUETAS_PERMITIDAS);
    }
}
