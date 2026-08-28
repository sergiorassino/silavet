<?php

namespace App\Support\ProtocoloNumero\Generators;

use App\Models\Paciente;
use App\Support\ProtocoloNumero\AbstractProtocoloNumeroGenerator;
use App\Support\ProtocoloNumero\ProtocoloNumeroContext;
use Illuminate\Support\Facades\DB;

/**
 * Secuencia numérica global: MAX(nombreProtocolo numérico) + 1 (ej. 13154 → 13155).
 * Solo considera valores compuestos exclusivamente por dígitos.
 */
class ConsecutivoSimpleGenerator extends AbstractProtocoloNumeroGenerator
{
    protected function lockKey(ProtocoloNumeroContext $ctx): string
    {
        return 'consecutivo_simple';
    }

    protected function calcularSiguiente(ProtocoloNumeroContext $ctx): string
    {
        $inicio = (int) config('tenant.protocolos.consecutivo_simple.inicio', 1);
        $max = $this->maximoNumericoExistente();

        if ($max === null) {
            return (string) $inicio;
        }

        return (string) ($max + 1);
    }

    protected function incrementar(string $numero, ProtocoloNumeroContext $ctx): string
    {
        return (string) ((int) $numero + 1);
    }

    private function maximoNumericoExistente(): ?int
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $max = Paciente::query()
                ->whereRaw("nombreProtocolo REGEXP '^[0-9]+$'")
                ->max(DB::raw('CAST(nombreProtocolo AS UNSIGNED)'));

            return $max !== null ? (int) $max : null;
        }

        $max = null;

        foreach (Paciente::query()->pluck('nombreProtocolo') as $valor) {
            $valor = (string) $valor;
            if ($valor === '' || ! ctype_digit($valor)) {
                continue;
            }

            $numerico = (int) $valor;
            if ($max === null || $numerico > $max) {
                $max = $numerico;
            }
        }

        return $max;
    }
}
