<?php

namespace App\Support\Tesoreria;

use App\Models\Paciente;

/**
 * Alta / edición de pago global (ingreso en `pacientes`, tipoRegistro = 2).
 */
final class PagoGlobalRegistro
{
    /**
     * @param  array{idClientes: int, pagado: float, idMediodepago: int, fechhoy?: string}  $datos
     */
    public static function guardar(?Paciente $existente, array $datos): Paciente
    {
        $payload = [
            'idClientes' => (int) $datos['idClientes'],
            'pagado' => round((float) $datos['pagado'], 2),
            'idMediodepago' => (int) $datos['idMediodepago'],
            'precio' => 0,
            'descuento' => 0,
            'saldo' => 0,
            'estado' => 'Pago',
            'idCuentasdetalle' => 0,
        ];

        if ($existente !== null) {
            $existente->update($payload);

            return $existente;
        }

        return Paciente::create(array_merge($payload, [
            'tipoRegistro' => Paciente::TIPO_INGRESO,
            'fechhoy' => (string) ($datos['fechhoy'] ?? now()->toDateString()),
            'nombre' => 'Pago global',
        ]));
    }

    public static function normalizarImporte(string $valor): string
    {
        $valor = trim(str_replace(' ', '', $valor));
        if ($valor === '') {
            return $valor;
        }

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (str_contains($valor, ',')) {
            $valor = str_replace(',', '.', $valor);
        }

        return $valor;
    }
}
