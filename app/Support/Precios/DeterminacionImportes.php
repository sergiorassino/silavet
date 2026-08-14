<?php

namespace App\Support\Precios;

/**
 * Importes de una fila de `determinaciones` (lista, descuento, precio con desc.).
 *
 * Alinea datos legacy (precio = lista, neto vacío) con el modelo nuevo
 * (neto = lista, precio = neto − descuento).
 */
final class DeterminacionImportes
{
    /**
     * @return array{neto: float, descuento: float, precio: float}
     */
    public static function resolver(float $neto, float $precio, float $descuento): array
    {
        $descuento = round(max(0, $descuento), 2);
        $neto = round($neto, 2);
        $precio = round($precio, 2);

        if ($neto <= 0 && $precio > 0) {
            $neto = $precio;
            $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);
        } elseif ($precio <= 0 && $neto > 0) {
            $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);
        }

        return [
            'neto' => $neto,
            'descuento' => $descuento,
            'precio' => $precio,
        ];
    }
}
