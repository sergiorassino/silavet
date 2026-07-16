<?php

namespace App\Support\Precios;

class DescuentoDeterminacionConfig
{
    public const MODO_CLIENTE_PORCENTAJE = 'cliente_porcentaje';

    public const MODO_PERFILES_VOLUMEN_MES_ANTERIOR = 'perfiles_volumen_mes_anterior';

    public static function implementacion(): string
    {
        return (string) config('tenant.precios.descuento', self::MODO_CLIENTE_PORCENTAJE);
    }

    public static function usaPorcentajeCliente(): bool
    {
        return self::implementacion() === self::MODO_CLIENTE_PORCENTAJE;
    }

    public static function usaPerfilesVolumenMesAnterior(): bool
    {
        return self::implementacion() === self::MODO_PERFILES_VOLUMEN_MES_ANTERIOR;
    }
}
