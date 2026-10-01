<?php

namespace App\Support;

/**
 * Comparación alfabética española: á/é/í/ó/ú/ü con su vocal,
 * ñ como letra propia después de n. Sin depender de ext-intl.
 */
final class OrdenAlfabeticoEspanol
{
    /** Marca que coloca ñ después de cualquier continuación de n y antes de o. */
    private const MARCA_ENIE = "n\x7F";

    /** @var array<string, string> */
    private const PLIEGUE_ACENTOS = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ç' => 'c',
    ];

    public static function comparar(string $a, string $b): int
    {
        $a = trim($a);
        $b = trim($b);

        $cmp = strnatcasecmp(self::plegar($a), self::plegar($b));
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp(mb_strtolower($a, 'UTF-8'), mb_strtolower($b, 'UTF-8'));
    }

    public static function plegar(string $texto): string
    {
        return str_replace('ñ', self::MARCA_ENIE, self::plegarAcentos($texto));
    }

    /**
     * Minúsculas y vocales acentuadas (y ç) como su letra base.
     * La ñ se conserva: es letra propia, no un acento.
     */
    public static function plegarAcentos(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        return str_replace(
            array_keys(self::PLIEGUE_ACENTOS),
            array_values(self::PLIEGUE_ACENTOS),
            $texto
        );
    }

    /** Búsqueda: el texto contiene la cadena aunque falten los acentos. */
    public static function contiene(string $texto, string $busqueda): bool
    {
        $busqueda = self::plegarAcentos(trim($busqueda));
        if ($busqueda === '') {
            return true;
        }

        return str_contains(self::plegarAcentos($texto), $busqueda);
    }
}
