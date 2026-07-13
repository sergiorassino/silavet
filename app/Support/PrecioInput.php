<?php

namespace App\Support;

class PrecioInput
{
    /** Formato argentino para pantalla: 25000.5 → 25.000,50 */
    public static function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0,00';
        }

        return number_format((float) $value, 2, ',', '.');
    }

    /** Convierte texto de pantalla (25.000,50) a float para BD. */
    public static function parse(string $value): float
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0.0;
        }

        $normalized = str_replace('.', '', $trimmed);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = preg_replace('/[^\d.\-]/', '', $normalized) ?? '0';

        return round((float) $normalized, 2);
    }
}
