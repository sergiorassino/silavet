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

    public function test_saldo_cuenta_corriente_true_por_defecto(): void
    {
        config(['tenant.portal_cliente.mostrar_saldo_cuenta_corriente' => true]);

        $this->assertTrue(PortalClienteConfig::mostrarSaldoCuentaCorriente());
    }

    public function test_saldo_cuenta_corriente_false_solo_si_se_declara(): void
    {
        config(['tenant.portal_cliente.mostrar_saldo_cuenta_corriente' => false]);

        $this->assertFalse(PortalClienteConfig::mostrarSaldoCuentaCorriente());
    }

    public function test_descuentos_obtenidos_true_por_defecto(): void
    {
        config(['tenant.portal_cliente.mostrar_descuentos_obtenidos' => true]);

        $this->assertTrue(PortalClienteConfig::mostrarDescuentosObtenidos());
    }

    public function test_descuentos_obtenidos_false_solo_si_se_declara(): void
    {
        config(['tenant.portal_cliente.mostrar_descuentos_obtenidos' => false]);

        $this->assertFalse(PortalClienteConfig::mostrarDescuentosObtenidos());
    }

    public function test_resumen_financiero_false_si_ambos_flags_estan_off(): void
    {
        config([
            'tenant.portal_cliente.mostrar_saldo_cuenta_corriente' => false,
            'tenant.portal_cliente.mostrar_descuentos_obtenidos' => false,
        ]);

        $this->assertFalse(PortalClienteConfig::mostrarResumenFinanciero());
    }

    public function test_resumen_financiero_true_si_al_menos_un_flag_esta_on(): void
    {
        config([
            'tenant.portal_cliente.mostrar_saldo_cuenta_corriente' => true,
            'tenant.portal_cliente.mostrar_descuentos_obtenidos' => false,
        ]);

        $this->assertTrue(PortalClienteConfig::mostrarResumenFinanciero());
    }
}
