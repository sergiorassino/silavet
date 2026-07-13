<?php

namespace App\Support\ProtocoloNumero;

use Carbon\Carbon;

final class ProtocoloNumeroContext
{
    public function __construct(
        public readonly Carbon $fecha,
        public readonly string $tipo = 'L',
    ) {}

    public static function fromFecha(Carbon|string $fecha, ?string $tipo = null): self
    {
        $tipo = $tipo ?? (string) config('tenant.protocolos.dual_corto_largo.tipo_default', 'L');

        return new self(
            Carbon::parse($fecha)->timezone('America/Argentina/Buenos_Aires'),
            strtoupper($tipo) === 'C' ? 'C' : 'L',
        );
    }

    public function fechaYmd(): string
    {
        return $this->fecha->format('ymd');
    }

    public function fechaSql(): string
    {
        return $this->fecha->format('Y-m-d');
    }
}
