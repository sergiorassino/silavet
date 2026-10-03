<?php

namespace App\Support\Protocolos;

/**
 * Columna «IA» del listado de pacientes, por tenant.
 *
 * Default true (`config/tenant.php`). Un laboratorio la oculta con
 * `asistente_ia.mostrar_columna => false` en `config/tenants/{slug}.php`.
 * El mismo flag vale para el Menú de Laboratorio y la autogestión.
 */
final class AsistenteIaConfig
{
    public static function mostrarColumna(): bool
    {
        return (bool) config('tenant.asistente_ia.mostrar_columna', true);
    }
}
