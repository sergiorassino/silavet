<?php

namespace App\Support;

class CuitInput
{
    public const DIGITS_LENGTH = 11;

    public const FORMATTED_LENGTH = 13;

    /** Extrae solo dígitos, máximo 11 (CUIT argentino). */
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return substr($digits, 0, self::DIGITS_LENGTH);
    }

    /** Formato de visualización: 99-99999999-9 */
    public static function format(string $value): string
    {
        $digits = self::normalize($value);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) <= 2) {
            return $digits;
        }

        if (strlen($digits) <= 10) {
            return substr($digits, 0, 2).'-'.substr($digits, 2);
        }

        return substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10, 1);
    }

    public static function isComplete(string $value): bool
    {
        return strlen(self::normalize($value)) === self::DIGITS_LENGTH;
    }
}
