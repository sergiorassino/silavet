<?php

namespace App\Support\ProtocoloNumero;

use App\Support\ProtocoloNumero\Generators\AnualConsecutivoGenerator;
use App\Support\ProtocoloNumero\Generators\DualCortoLargoGenerator;
use App\Support\ProtocoloNumero\Generators\FechaDiariaGenerator;
use InvalidArgumentException;

class ProtocoloNumeroRegistry
{
    /** @var array<string, class-string<ProtocoloNumeroGenerator>> */
    private const MAP = [
        'anual_consecutivo' => AnualConsecutivoGenerator::class,
        'fecha_diaria' => FechaDiariaGenerator::class,
        'dual_corto_largo' => DualCortoLargoGenerator::class,
    ];

    public static function resolver(): ProtocoloNumeroGenerator
    {
        $implementacion = (string) config('tenant.protocolos.implementacion', 'fecha_diaria');

        if (! isset(self::MAP[$implementacion])) {
            throw new InvalidArgumentException("Implementación de protocolo desconocida: {$implementacion}");
        }

        return app(self::MAP[$implementacion]);
    }

    public static function usaTipoProtocolo(): bool
    {
        return config('tenant.protocolos.implementacion') === 'dual_corto_largo';
    }
}
