<?php

namespace App\Support\Precios;

use App\Models\Estimacioncosto;
use App\Models\Tipodeterminacion;
use Illuminate\Support\Facades\Schema;

class PrecioDeterminacionResolver
{
    /**
     * Resuelve el precio de lista 1 para una determinación y cliente.
     * Prioridad: estimacioncostos (precio especial) → tipodeterminaciones.precio.
     */
    public static function resolverPrecioLista1(int $idClientes, Tipodeterminacion $tipo): float
    {
        if (Schema::hasTable('estimacioncostos')) {
            $override = Estimacioncosto::query()
                ->where('idClientes', $idClientes)
                ->where('idTipodeterminaciones', $tipo->idTipodeterminaciones)
                ->value('precio');

            if ($override !== null) {
                return round((float) $override, 2);
            }
        }

        return round((float) $tipo->precio, 2);
    }

    /** Calcula el descuento en pesos a partir del porcentaje del cliente. */
    public static function calcularDescuento(float $precio, float $porcentajeCliente): float
    {
        if ($precio <= 0 || $porcentajeCliente <= 0) {
            return 0.0;
        }

        return round($precio * ($porcentajeCliente / 100), 2);
    }

    /** Valor neto de una línea (precio − descuento). */
    public static function neto(float $precio, float $descuento): float
    {
        return round(max(0, $precio - $descuento), 2);
    }
}
