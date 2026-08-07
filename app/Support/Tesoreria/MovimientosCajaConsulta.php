<?php

namespace App\Support\Tesoreria;

use App\Models\Movimiento;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta del listado de caja labvetciudad (tabla `movimientos`).
 */
final class MovimientosCajaConsulta
{
    /**
     * Misma lógica de filtro que la grilla de `MovimientosCajaIndex`.
     *
     * @param  ?string  $fechaDesde  Y-m-d inclusive; null = sin límite inferior
     * @param  ?string  $fechaHasta  Y-m-d inclusive; null = sin límite superior
     * @return Builder<Movimiento>
     */
    public static function listado(
        string $busqueda = '',
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
    ): Builder {
        return Movimiento::query()
            ->with([
                'cuenta:id,nombreMedioPago',
                'tipoMovimiento:id,tipoMovimiento',
                'cliente:idClientes,nombre',
                'paciente:idPacientes,nombre,fechhoy',
                'concepto:id,concepto',
                'proveedor:id,proveedor',
            ])
            ->when($fechaDesde !== null, fn ($q) => $q->whereDate('fechhora', '>=', $fechaDesde))
            ->when($fechaHasta !== null, fn ($q) => $q->whereDate('fechhora', '<=', $fechaHasta))
            ->when(trim($busqueda) !== '', function ($q) use ($busqueda) {
                $term = trim($busqueda);
                $q->where(function ($inner) use ($term) {
                    $inner->where('obs', 'like', "%{$term}%")
                        ->orWhere('comprobante', 'like', "%{$term}%")
                        ->orWhere('monto', 'like', "%{$term}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"))
                        ->orWhereHas('cuenta', fn ($c) => $c->where('nombreMedioPago', 'like', "%{$term}%"))
                        ->orWhereHas('concepto', fn ($c) => $c->where('concepto', 'like', "%{$term}%"))
                        ->orWhereHas('proveedor', fn ($c) => $c->where('proveedor', 'like', "%{$term}%"))
                        ->orWhereHas('paciente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"));

                    if (ctype_digit($term)) {
                        $inner->orWhere('id', (int) $term)
                            ->orWhere('idPacientes', (int) $term);
                    }
                });
            })
            ->orderByDesc('fechhora')
            ->orderByDesc('id');
    }
}
