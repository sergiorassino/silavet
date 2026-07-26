<?php

namespace App\Support\Entorno;

use App\Models\Entorno;
use Illuminate\Support\Facades\Schema;

/**
 * Tinte de fondo de la UI staff (fondo, hero, sidebar) con degradé.
 * No afecta colorInforme (PDFs).
 */
final class TemaFondoSistema
{
    public const DEFAULT = '#0EA5E9';

    public static function colorBase(): string
    {
        return once(function () {
            if (! Schema::hasTable('entorno') || ! Schema::hasColumn('entorno', 'colorFondoSistema')) {
                return self::DEFAULT;
            }

            $entorno = Entorno::query()->orderBy('id')->first();
            $color = trim((string) ($entorno?->colorFondoSistema ?? ''));

            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
                return strtoupper($color);
            }

            return self::DEFAULT;
        });
    }

    /**
     * Variable CSS base; el resto del degradé se deriva en sidebar-bosque-head / app.css.
     */
    public static function variablesCss(): string
    {
        return '--vl-theme-base: '.self::colorBase().';';
    }
}
