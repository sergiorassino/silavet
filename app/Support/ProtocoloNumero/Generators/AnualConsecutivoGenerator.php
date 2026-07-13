<?php

namespace App\Support\ProtocoloNumero\Generators;

use App\Models\Paciente;
use App\Support\ProtocoloNumero\AbstractProtocoloNumeroGenerator;
use App\Support\ProtocoloNumero\ProtocoloNumeroContext;

class AnualConsecutivoGenerator extends AbstractProtocoloNumeroGenerator
{
    protected function lockKey(ProtocoloNumeroContext $ctx): string
    {
        return 'anual_'.$this->prefijoAnio($ctx);
    }

    protected function calcularSiguiente(ProtocoloNumeroContext $ctx): string
    {
        $prefijo = $this->prefijoAnio($ctx);
        $longitudSecuencia = (int) config('tenant.protocolos.anual_consecutivo.longitud_secuencia', 5);
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
        $prefijo = $this->prefijoAnio($ctx);
        $longitudSecuencia = (int) config('tenant.protocolos.anual_consecutivo.longitud_secuencia', 5);
        $secuencia = (int) substr($numero, strlen($prefijo)) + 1;

        return $this->formatear($prefijo, $secuencia, $longitudSecuencia);
    }

    private function prefijoAnio(ProtocoloNumeroContext $ctx): string
    {
        return $ctx->fecha->format('y');
    }

    private function formatear(string $prefijo, int $secuencia, int $longitudSecuencia): string
    {
        return $prefijo.str_pad((string) $secuencia, $longitudSecuencia, '0', STR_PAD_LEFT);
    }
}
