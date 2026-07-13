<?php

namespace App\Support\ProtocoloNumero;

interface ProtocoloNumeroGenerator
{
    public function previsualizar(ProtocoloNumeroContext $ctx): string;

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public function withSiguienteReservado(ProtocoloNumeroContext $ctx, callable $callback): mixed;
}
