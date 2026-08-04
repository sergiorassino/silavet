<?php

namespace App\Support\ProtocoloNumero\Generators;

use App\Support\ProtocoloNumero\ProtocoloNumeroContext;
use App\Support\ProtocoloNumero\ProtocoloNumeroGenerator;

/**
 * No genera número: deja nombreProtocolo vacío al alta.
 * Varios protocolos pueden compartir el valor vacío (no hay unicidad).
 */
class VacioGenerator implements ProtocoloNumeroGenerator
{
    public function previsualizar(ProtocoloNumeroContext $ctx): string
    {
        return '';
    }

    public function withSiguienteReservado(ProtocoloNumeroContext $ctx, callable $callback): mixed
    {
        return $callback('');
    }
}
