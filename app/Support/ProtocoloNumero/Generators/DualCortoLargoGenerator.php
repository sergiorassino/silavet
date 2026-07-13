<?php

namespace App\Support\ProtocoloNumero\Generators;

use App\Models\Paciente;
use App\Support\ProtocoloNumero\AbstractProtocoloNumeroGenerator;
use App\Support\ProtocoloNumero\ProtocoloNumeroContext;

class DualCortoLargoGenerator extends AbstractProtocoloNumeroGenerator
{
    protected function lockKey(ProtocoloNumeroContext $ctx): string
    {
        if ($ctx->tipo === 'C') {
            return 'corto';
        }

        return 'largo_'.$ctx->fechaYmd();
    }

    protected function calcularSiguiente(ProtocoloNumeroContext $ctx): string
    {
        return $ctx->tipo === 'C'
            ? $this->calcularCorto()
            : $this->calcularLargo($ctx);
    }

    protected function incrementar(string $numero, ProtocoloNumeroContext $ctx): string
    {
        if ($ctx->tipo === 'C') {
            $prefijo = (string) config('tenant.protocolos.dual_corto_largo.corto_prefijo', 'C');
            $longitud = (int) config('tenant.protocolos.dual_corto_largo.corto_longitud', 9);
            $secuencia = (int) substr($numero, strlen($prefijo)) + 1;

            return $this->formatearCorto($secuencia, $prefijo, $longitud);
        }

        $prefijo = $ctx->fechaYmd();
        $longitudSecuencia = (int) config('tenant.protocolos.dual_corto_largo.largo_secuencia_len', 3);
        $secuencia = (int) substr($numero, strlen($prefijo)) + 1;

        return $this->formatearLargo($prefijo, $secuencia, $longitudSecuencia);
    }

    private function calcularLargo(ProtocoloNumeroContext $ctx): string
    {
        $prefijo = $ctx->fechaYmd();
        $longitudSecuencia = (int) config('tenant.protocolos.dual_corto_largo.largo_secuencia_len', 3);
        $longitud = strlen($prefijo) + $longitudSecuencia;

        $ultimo = Paciente::query()
            ->whereDate('fechhoy', $ctx->fechaSql())
            ->where('nombreProtocolo', 'not like', 'C%')
            ->whereRaw('CHAR_LENGTH(nombreProtocolo) = ?', [$longitud])
            ->orderByDesc('nombreProtocolo')
            ->value('nombreProtocolo');

        $secuencia = 1;
        if ($ultimo !== null && strlen($ultimo) >= $longitud) {
            $secuencia = (int) substr($ultimo, strlen($prefijo)) + 1;
        }

        return $this->formatearLargo($prefijo, $secuencia, $longitudSecuencia);
    }

    private function calcularCorto(): string
    {
        $prefijo = (string) config('tenant.protocolos.dual_corto_largo.corto_prefijo', 'C');
        $inicio = (int) config('tenant.protocolos.dual_corto_largo.corto_inicio', 101);
        $longitud = (int) config('tenant.protocolos.dual_corto_largo.corto_longitud', 9);

        $ultimoNumerico = Paciente::query()
            ->where('nombreProtocolo', 'like', $prefijo.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(nombreProtocolo, ?) AS UNSIGNED)) as max_num', [strlen($prefijo) + 1])
            ->value('max_num');

        $secuencia = $ultimoNumerico !== null && $ultimoNumerico !== ''
            ? (int) $ultimoNumerico + 1
            : $inicio;

        return $this->formatearCorto($secuencia, $prefijo, $longitud);
    }

    private function formatearLargo(string $prefijo, int $secuencia, int $longitudSecuencia): string
    {
        return $prefijo.str_pad((string) $secuencia, $longitudSecuencia, '0', STR_PAD_LEFT);
    }

    private function formatearCorto(int $secuencia, string $prefijo, int $longitud): string
    {
        return $prefijo.str_pad((string) $secuencia, $longitud, '0', STR_PAD_LEFT);
    }
}
