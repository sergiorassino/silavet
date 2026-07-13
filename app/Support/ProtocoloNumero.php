<?php

namespace App\Support;

use App\Support\ProtocoloNumero\ProtocoloNumeroContext;
use App\Support\ProtocoloNumero\ProtocoloNumeroRegistry;
use Carbon\Carbon;

class ProtocoloNumero
{
    /** Vista previa sin reservar (puede cambiar antes de guardar). */
    public static function previsualizarParaFecha(Carbon|string $fecha, ?string $tipo = null): string
    {
        return self::previsualizar(ProtocoloNumeroContext::fromFecha($fecha, $tipo));
    }

    public static function previsualizar(ProtocoloNumeroContext $ctx): string
    {
        return ProtocoloNumeroRegistry::resolver()->previsualizar($ctx);
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public static function withSiguienteReservado(Carbon|string $fecha, callable $callback, ?string $tipo = null): mixed
    {
        return self::withContextoReservado(ProtocoloNumeroContext::fromFecha($fecha, $tipo), $callback);
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public static function withContextoReservado(ProtocoloNumeroContext $ctx, callable $callback): mixed
    {
        return ProtocoloNumeroRegistry::resolver()->withSiguienteReservado($ctx, $callback);
    }

    public static function usaTipoProtocolo(): bool
    {
        return ProtocoloNumeroRegistry::usaTipoProtocolo();
    }
}
