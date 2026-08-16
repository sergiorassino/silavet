<?php

namespace App\Support\Tipodeterminaciones;

use App\Support\Precios\DescuentoDeterminacionConfig;

class TipodeterminacionesGridConfig
{
    public static function mostrarColumnaPerfil(): bool
    {
        if (DescuentoDeterminacionConfig::usaPerfilesVolumenMesAnterior()) {
            return false;
        }

        return (bool) config('tenant.tipodeterminaciones.mostrar_columna_perfil', false);
    }

    public static function derivacionEsCatalogo(): bool
    {
        return (string) config('tenant.tipodeterminaciones.derivacion', 'si_no') === 'catalogo';
    }

    /**
     * Columna de tipodeterminaciones donde persiste el centro elegido en modo catálogo.
     * El sistema legacy (ScriptCase) guardó el FK en `derivacion`, no en `destino`
     * (`destino` es un código 0–3 de otra semántica).
     */
    public static function columnaFkCentro(bool $tieneColumnaDerivacion): string
    {
        return (self::derivacionEsCatalogo() && $tieneColumnaDerivacion)
            ? 'derivacion'
            : 'destino';
    }

    public static function columnasVisibles(bool $tienePrecioExtra): int
    {
        $columnas = 5; // acciones, orden, nombre, precio, derivación

        if ($tienePrecioExtra) {
            $columnas += 2;
        }

        if (self::mostrarColumnaPerfil()) {
            $columnas += 1;
        }

        return $columnas;
    }
}
