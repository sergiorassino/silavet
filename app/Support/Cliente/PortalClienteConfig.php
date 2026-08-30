<?php

namespace App\Support\Cliente;

/**
 * Flags del Menú de Clientes (autogestión).
 * Solo afectan rutas `/cliente/…` y el sidebar `sidebar-nav-cliente`.
 * No ocultan módulos del menú de laboratorio (p. ej. Estimación en Listados).
 */
final class PortalClienteConfig
{
    /**
     * Ítem Lista de Precios y rutas cliente.lista-precios*.
     * Default true. Solo se oculta si el tenant declara `mostrar_lista_precios => false`.
     */
    public static function mostrarListaPrecios(): bool
    {
        return (bool) config('tenant.portal_cliente.mostrar_lista_precios', true);
    }

    /**
     * Ítem Estimación de Costos y ruta cliente.estimacion-costos.
     * Default true. Solo se oculta si el tenant declara `mostrar_estimacion_costos => false`.
     * La ruta staff `listados.estimacion-costos` no usa este flag.
     */
    public static function mostrarEstimacionCostos(): bool
    {
        return (bool) config('tenant.portal_cliente.mostrar_estimacion_costos', true);
    }
}
