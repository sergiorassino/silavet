<?php

namespace App\Support\Facturacion;

use RuntimeException;

/**
 * Desglose de IVA y letra del comprobante para un laboratorio responsable inscripto.
 * El régimen monotributo no usa esta clase al emitir.
 */
final class FacturacionIva
{
    public const CONDICION_RI = 1;

    public const CONDICION_EXENTO = 4;

    public const CONDICION_CONSUMIDOR_FINAL = 5;

    public const CONDICION_MONOTRIBUTO = 6;

    public const CBTE_FACTURA_A = 1;

    public const CBTE_NC_A = 3;

    public const CBTE_FACTURA_B = 6;

    public const CBTE_NC_B = 8;

    /**
     * Porcentaje de alícuota → código AFIP (FEParamGetTiposIva).
     *
     * @var array<string, int>
     */
    private const ALICUOTAS_AFIP = [
        '0' => 3,
        '2.5' => 9,
        '5' => 8,
        '10.5' => 4,
        '21' => 5,
        '27' => 6,
    ];

    /**
     * @return array{neto: float, iva: float, total: float, alicuota: float, alic_id: int}
     */
    public static function desglosar(float $importe): array
    {
        $importe = round($importe, 2);
        if ($importe <= 0) {
            throw new RuntimeException('El importe a facturar debe ser mayor a cero.');
        }

        $alicuota = FacturacionAfipConfig::alicuotaIva();
        $alicId = self::idAlicuota($alicuota);
        $tasa = $alicuota / 100;

        if (FacturacionAfipConfig::precioIncluyeIva()) {
            $total = $importe;
            $neto = $tasa > 0 ? round($total / (1 + $tasa), 2) : $total;
            $iva = round($total - $neto, 2);
        } else {
            $neto = $importe;
            $iva = round($neto * $tasa, 2);
            $total = round($neto + $iva, 2);
        }

        return [
            'neto' => $neto,
            'iva' => $iva,
            'total' => $total,
            'alicuota' => $alicuota,
            'alic_id' => $alicId,
        ];
    }

    public static function idAlicuota(float $porcentaje): int
    {
        $clave = self::claveAlicuota($porcentaje);
        if (! isset(self::ALICUOTAS_AFIP[$clave])) {
            throw new RuntimeException(
                'La alícuota de IVA '.$clave.'% no es válida. Use 0, 2.5, 5, 10.5, 21 o 27.'
            );
        }

        return self::ALICUOTAS_AFIP[$clave];
    }

    public static function tipoFactura(int $condicionIvaReceptorId): int
    {
        return $condicionIvaReceptorId === self::CONDICION_RI
            ? self::CBTE_FACTURA_A
            : self::CBTE_FACTURA_B;
    }

    public static function tipoNotaCredito(int $cbteTipoFactura): int
    {
        return match ($cbteTipoFactura) {
            self::CBTE_FACTURA_A => self::CBTE_NC_A,
            self::CBTE_FACTURA_B => self::CBTE_NC_B,
            default => (int) FacturacionAfipConfig::config()['nota_credito_tipo'],
        };
    }

    public static function discriminaIva(int $cbteTipo): bool
    {
        return in_array($cbteTipo, [
            self::CBTE_FACTURA_A,
            self::CBTE_NC_A,
            self::CBTE_FACTURA_B,
            self::CBTE_NC_B,
        ], true);
    }

    public static function letra(int $cbteTipo): string
    {
        return match ($cbteTipo) {
            self::CBTE_FACTURA_A, self::CBTE_NC_A => 'A',
            self::CBTE_FACTURA_B, self::CBTE_NC_B => 'B',
            default => 'C',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function opcionesCondicion(bool $permiteResponsableInscripto, bool $soloConsumidorFinal): array
    {
        if ($soloConsumidorFinal) {
            return [
                self::CONDICION_CONSUMIDOR_FINAL => 'Consumidor final',
            ];
        }

        $opciones = [
            self::CONDICION_CONSUMIDOR_FINAL => 'Consumidor final',
            self::CONDICION_MONOTRIBUTO => 'Responsable monotributo',
            self::CONDICION_EXENTO => 'Exento',
        ];

        if ($permiteResponsableInscripto) {
            return [self::CONDICION_RI => 'Responsable inscripto'] + $opciones;
        }

        return $opciones;
    }

    public static function etiquetaAlicuota(float $porcentaje): string
    {
        $texto = number_format($porcentaje, 2, ',', '.');

        return rtrim(rtrim($texto, '0'), ',');
    }

    private static function claveAlicuota(float $porcentaje): string
    {
        $normalizado = round($porcentaje, 2);
        if (abs($normalizado - round($normalizado)) < 0.001) {
            return (string) (int) round($normalizado);
        }

        return rtrim(rtrim(number_format($normalizado, 1, '.', ''), '0'), '.');
    }
}
