<?php

namespace App\Support\CuentaCorriente;

use App\Support\Tesoreria\TesoreriaConfig;

/**
 * Punto único para obtener el saldo del cliente y formatear moneda,
 * independientemente de la variante de tesorería activa.
 *
 * - `tesoreria_movimientos` (NeoLab / mayoría) → CuentaCorrienteConsulta (tabla `pacientes`).
 * - `tesoreria_pacientes` (labvetciudad)        → CuentaCorrienteMovimientosConsulta (tabla `movimientos`).
 *
 * Callers: DashboardClienteConsulta, DescuentoDeterminacionResolver,
 *          PacienteIndex (autogestión).
 */
final class CuentaCorrienteFacade
{
    public static function saldoClienteHoy(int $idClientes): float
    {
        return TesoreriaConfig::usaPacientes()
            ? CuentaCorrienteMovimientosConsulta::saldoClienteHoy($idClientes)
            : CuentaCorrienteConsulta::saldoClienteHoy($idClientes);
    }

    public static function formatearMoneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
