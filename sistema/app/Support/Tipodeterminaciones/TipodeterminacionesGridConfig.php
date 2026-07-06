<?php

namespace App\Support\Tipodeterminaciones;

class TipodeterminacionesGridConfig
{
    public static function mostrarColumnaPerfil(): bool
    {
        return (bool) config('tenant.tipodeterminaciones.mostrar_columna_perfil', false);
    }

    public static function derivacionEsCatalogo(): bool
    {
        return (string) config('tenant.tipodeterminaciones.derivacion', 'si_no') === 'catalogo';
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
