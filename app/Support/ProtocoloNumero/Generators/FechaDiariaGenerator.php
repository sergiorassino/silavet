<?php

namespace App\Support\ProtocoloNumero\Generators;

use App\Models\Paciente;
use App\Support\ProtocoloNumero\AbstractProtocoloNumeroGenerator;
use App\Support\ProtocoloNumero\ProtocoloNumeroContext;

class FechaDiariaGenerator extends AbstractProtocoloNumeroGenerator
{
    protected function lockKey(ProtocoloNumeroContext $ctx): string
    {
        return $ctx->fechaYmd();
    }

    protected function calcularSiguiente(ProtocoloNumeroContext $ctx): string
    {
        $prefijo = $ctx->fechaYmd();
        $longitudSecuencia = (int) config('tenant.protocolos.fecha_diaria.longitud_secuencia', 3);
        $longitud = strlen($prefijo) + $longitudSecuencia;

        $ultimo = Paciente::query()
            ->where('nombreProtocolo', 'like', $prefijo.'%')
            ->whereRaw('CHAR_LENGTH(nombreProtocolo) = ?', [$longitud])
            ->orderByDesc('nombreProtocolo')
            ->value('nombreProtocolo');

        $secuencia = 1;
        if ($ultimo !== null && strlen($ultimo) >= $longitud) {
            $secuencia = (int) substr($ultimo, strlen($prefijo)) + 1;
        }

        return $this->formatear($prefijo, $secuencia, $longitudSecuencia);
    }

    protected function incrementar(string $numero, ProtocoloNumeroContext $ctx): string
    {
        $prefijo = $ctx->fechaYmd();
        $longitudSecuencia = (int) config('tenant.protocolos.fecha_diaria.longitud_secuencia', 3);
        $secuencia = (int) substr($numero, strlen($prefijo)) + 1;

        return $this->formatear($prefijo, $secuencia, $longitudSecuencia);
    }

    private function formatear(string $prefijo, int $secuencia, int $longitudSecuencia): string
    {
        return $prefijo.str_pad((string) $secuencia, $longitudSecuencia, '0', STR_PAD_LEFT);
    }
}
