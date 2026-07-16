<?php

namespace App\Support\Precios;

use App\Models\Cliente;
use App\Models\Tipodeterminacion;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use Carbon\CarbonInterface;

class DescuentoDeterminacionResolver
{
    public static function porcentaje(
        int $idClientes,
        Tipodeterminacion $tipo,
        ?CarbonInterface $fechaReferencia = null
    ): float {
        if (DescuentoDeterminacionConfig::usaPorcentajeCliente()) {
            return self::porcentajeCliente($idClientes);
        }

        if (DescuentoDeterminacionConfig::usaPerfilesVolumenMesAnterior()) {
            return self::porcentajePerfilesVolumen($idClientes, $tipo, $fechaReferencia);
        }

        return 0.0;
    }

    public static function calcularDescuento(
        float $neto,
        int $idClientes,
        Tipodeterminacion $tipo,
        ?CarbonInterface $fechaReferencia = null
    ): float {
        return PrecioDeterminacionResolver::calcularDescuento(
            $neto,
            self::porcentaje($idClientes, $tipo, $fechaReferencia)
        );
    }

    /**
     * @return array{
     *     modo: string,
     *     porcentaje?: float,
     *     perfilesMesAnterior?: int,
     *     porcentajePerfiles?: float,
     *     mesAnteriorLabel?: string,
     *     texto: string
     * }
     */
    public static function resumenParaCliente(int $idClientes, ?CarbonInterface $fechaReferencia = null): array
    {
        $fecha = $fechaReferencia ?? now();

        if (DescuentoDeterminacionConfig::usaPorcentajeCliente()) {
            $porcentaje = self::porcentajeCliente($idClientes);

            return [
                'modo' => DescuentoDeterminacionConfig::MODO_CLIENTE_PORCENTAJE,
                'porcentaje' => $porcentaje,
                'texto' => $porcentaje > 0
                    ? number_format($porcentaje, 2, ',', '.').'% sobre todas las determinaciones'
                    : 'Sin descuento configurado en el cliente',
            ];
        }

        if (DescuentoDeterminacionConfig::usaPerfilesVolumenMesAnterior()) {
            $perfilesMesAnterior = DescuentoPerfilesVolumenConsulta::cantidadPerfilesMesAnterior($idClientes, $fecha);
            $porcentajePerfiles = DescuentoPerfilesVolumenConsulta::porcentajePorCantidad($perfilesMesAnterior);
            $mesAnteriorLabel = $fecha->copy()->startOfMonth()->subMonth()->translatedFormat('F Y');

            return [
                'modo' => DescuentoDeterminacionConfig::MODO_PERFILES_VOLUMEN_MES_ANTERIOR,
                'perfilesMesAnterior' => $perfilesMesAnterior,
                'porcentajePerfiles' => $porcentajePerfiles,
                'mesAnteriorLabel' => $mesAnteriorLabel,
                'texto' => $porcentajePerfiles > 0
                    ? number_format($porcentajePerfiles, 1, ',', '.').'% en perfiles del mes actual ('.$perfilesMesAnterior.' perfiles en '.$mesAnteriorLabel.')'
                    : 'Sin descuento en perfiles ('.$perfilesMesAnterior.' perfiles en '.$mesAnteriorLabel.')',
            ];
        }

        return [
            'modo' => 'ninguno',
            'texto' => 'Sin descuento',
        ];
    }

    /**
     * Datos del encabezado de pacientes en autogestión del cliente.
     *
     * @return array{
     *     modo: string,
     *     saldo: float,
     *     saldoFormateado: string,
     *     mostrarDetalleVolumen: bool,
     *     descuentosMes?: float,
     *     descuentosMesFormateado?: string,
     *     perfilesMesAnterior?: int,
     *     porcentajeEsteMes?: float,
     *     porcentajeEsteMesFormateado?: string,
     *     perfilesMesActual?: int,
     *     mensajeProximoUmbral?: string
     * }
     */
    public static function encabezadoAutogestion(int $idClientes, ?CarbonInterface $fechaReferencia = null): array
    {
        $fecha = $fechaReferencia ?? now();
        $saldo = CuentaCorrienteConsulta::saldoClienteHoy($idClientes);
        $saldoFormateado = CuentaCorrienteConsulta::formatearMoneda($saldo);

        if (! DescuentoDeterminacionConfig::usaPerfilesVolumenMesAnterior()) {
            return [
                'modo' => DescuentoDeterminacionConfig::implementacion(),
                'saldo' => $saldo,
                'saldoFormateado' => $saldoFormateado,
                'mostrarDetalleVolumen' => false,
            ];
        }

        $perfilesMesAnterior = DescuentoPerfilesVolumenConsulta::cantidadPerfilesMesAnterior($idClientes, $fecha);
        $porcentajeEsteMes = DescuentoPerfilesVolumenConsulta::porcentajePorCantidad($perfilesMesAnterior);
        $perfilesMesActual = DescuentoPerfilesVolumenConsulta::cantidadPerfilesMesActual($idClientes, $fecha);
        $descuentosMes = DescuentoPerfilesVolumenConsulta::sumaDescuentosMesActual($idClientes, $fecha);
        $proximo = DescuentoPerfilesVolumenConsulta::proximoUmbral($perfilesMesActual);

        if ($proximo === null) {
            $pctMax = (float) DescuentoPerfilesVolumenConsulta::UMBRALES[array_key_last(DescuentoPerfilesVolumenConsulta::UMBRALES)];
            $mensajeProximo = 'Ya alcanzaste el máximo descuento ('
                .DescuentoPerfilesVolumenConsulta::formatearPorcentaje($pctMax)
                .') para todos tus pedidos del mes próximo';
        } else {
            $mensajeProximo = 'Te faltan '.$proximo['faltan'].' Perfiles para obtener el '
                .DescuentoPerfilesVolumenConsulta::formatearPorcentaje($proximo['porcentaje'])
                .' de descuento en todos tus pedidos del mes próximo';
        }

        return [
            'modo' => DescuentoDeterminacionConfig::MODO_PERFILES_VOLUMEN_MES_ANTERIOR,
            'saldo' => $saldo,
            'saldoFormateado' => $saldoFormateado,
            'mostrarDetalleVolumen' => true,
            'descuentosMes' => $descuentosMes,
            'descuentosMesFormateado' => CuentaCorrienteConsulta::formatearMoneda($descuentosMes),
            'perfilesMesAnterior' => $perfilesMesAnterior,
            'porcentajeEsteMes' => $porcentajeEsteMes,
            'porcentajeEsteMesFormateado' => DescuentoPerfilesVolumenConsulta::formatearPorcentaje($porcentajeEsteMes),
            'perfilesMesActual' => $perfilesMesActual,
            'mensajeProximoUmbral' => $mensajeProximo,
        ];
    }

    private static function porcentajeCliente(int $idClientes): float
    {
        $cliente = Cliente::query()
            ->whereKey($idClientes)
            ->first(['descuento']);

        return (float) ($cliente?->descuento ?? 0);
    }

    private static function porcentajePerfilesVolumen(
        int $idClientes,
        Tipodeterminacion $tipo,
        ?CarbonInterface $fechaReferencia
    ): float {
        if ((int) ($tipo->perfil ?? 0) <= 0) {
            return 0.0;
        }

        $fecha = $fechaReferencia ?? now();
        $cantidad = DescuentoPerfilesVolumenConsulta::cantidadPerfilesMesAnterior($idClientes, $fecha);

        return DescuentoPerfilesVolumenConsulta::porcentajePorCantidad($cantidad);
    }
}
