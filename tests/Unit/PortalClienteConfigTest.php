<?php

namespace Tests\Unit;

use App\Support\Cliente\PortalClienteConfig;
use Tests\TestCase;

class PortalClienteConfigTest extends TestCase
{
    public function test_lista_precios_true_por_defecto(): void
    {
        config(['tenant.portal_cliente.mostrar_lista_precios' => true]);

        $this->assertTrue(PortalClienteConfig::mostrarListaPrecios());
    }

    public function test_lista_precios_false_solo_si_se_declara(): void
    {
        config(['tenant.portal_cliente.mostrar_lista_precios' => false]);

        $this->assertFalse(PortalClienteConfig::mostrarListaPrecios());
    }

    public function test_estimacion_costos_true_por_defecto(): void
    {
        config(['tenant.portal_cliente.mostrar_estimacion_costos' => true]);

        $this->assertTrue(PortalClienteConfig::mostrarEstimacionCostos());
    }

    public function test_estimacion_costos_false_solo_si_se_declara(): void
    {
        config(['tenant.portal_cliente.mostrar_estimacion_costos' => false]);

        $this->assertFalse(PortalClienteConfig::mostrarEstimacionCostos());
    }
}
