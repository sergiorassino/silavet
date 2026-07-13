<?php

namespace App\Support\ProtocoloNumero;

use App\Models\Paciente;
use App\Support\ProtocoloNumero\Concerns\ReservaConLock;
use Illuminate\Support\Facades\DB;

abstract class AbstractProtocoloNumeroGenerator implements ProtocoloNumeroGenerator
{
    use ReservaConLock;

    public function previsualizar(ProtocoloNumeroContext $ctx): string
    {
        return $this->calcularSiguiente($ctx);
    }

    public function withSiguienteReservado(ProtocoloNumeroContext $ctx, callable $callback): mixed
    {
        return $this->withLock($this->lockKey($ctx), function () use ($ctx, $callback) {
            return DB::transaction(function () use ($ctx, $callback) {
                $numero = $this->calcularSiguiente($ctx);

                while (Paciente::query()->where('nombreProtocolo', $numero)->exists()) {
                    $numero = $this->incrementar($numero, $ctx);
                }

                return $callback($numero);
            });
        });
    }

    abstract protected function lockKey(ProtocoloNumeroContext $ctx): string;

    abstract protected function calcularSiguiente(ProtocoloNumeroContext $ctx): string;

    abstract protected function incrementar(string $numero, ProtocoloNumeroContext $ctx): string;
}
