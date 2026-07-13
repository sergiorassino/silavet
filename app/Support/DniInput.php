<?php

namespace App\Support;

class DniInput
{
    public const MAX_LENGTH = 10;

    /** Normaliza el usuario de login: solo alfanumérico ASCII, sin espacios. */
    public static function normalize(string $value, int $maxLength = self::MAX_LENGTH): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', '', $value) ?? '';

        return substr($normalized, 0, $maxLength);
    }
}
