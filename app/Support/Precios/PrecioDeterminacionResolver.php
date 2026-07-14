<?php

namespace App\Support\Precios;

use App\Models\Tipodeterminacion;

class PrecioDeterminacionResolver
{
    /**
     * Precio de lista 1: tipodeterminaciones.precio.
     *
     * Nota: la tabla legacy estimacioncostos no se usa en la versión nueva
     * (se mantiene solo por compatibilidad con el sistema viejo).
     */
    public static function resolverPrecioLista1(Tipodeterminacion $tipo): float
    {
        return round((float) $tipo->precio, 2);
    }

    /** Calcula el descuento en pesos a partir del porcentaje del cliente sobre el neto (lista). */
    public static function calcularDescuento(float $neto, float $porcentajeCliente): float
    {
        if ($neto <= 0 || $porcentajeCliente <= 0) {
            return 0.0;
        }

        return round($neto * ($porcentajeCliente / 100), 2);
    }

    /** Precio con descuento: neto (lista) − descuento en pesos. */
    public static function precioConDescuento(float $neto, float $descuento): float
    {
        return round(max(0, $neto - $descuento), 2);
    }

    /**
     * @deprecated Preferir precioConDescuento(). Conservado por cuenta corriente y código legacy
     *             donde el primer argumento era el importe de lista.
     */
    public static function neto(float $precio, float $descuento): float
    {
        return self::precioConDescuento($precio, $descuento);
    }
}
