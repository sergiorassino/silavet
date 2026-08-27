<?php

namespace Tests\Unit;

use App\Support\Facturacion\FacturacionAfipConfig;
use Tests\TestCase;

class FacturacionAfipConfigTest extends TestCase
{
    public function test_en_listado_pacientes_solo_si_habilitada_y_modo_paciente(): void
    {
        config([
            'tenant.facturacion_afip.habilitado' => true,
            'tenant.facturacion_afip.modo' => FacturacionAfipConfig::MODO_PACIENTE,
        ]);
        $this->assertTrue(FacturacionAfipConfig::enListadoPacientes());

        config(['tenant.facturacion_afip.habilitado' => false]);
        $this->assertFalse(FacturacionAfipConfig::enListadoPacientes());

        config([
            'tenant.facturacion_afip.habilitado' => true,
            'tenant.facturacion_afip.modo' => FacturacionAfipConfig::MODO_MOVIMIENTO,
        ]);
        $this->assertFalse(FacturacionAfipConfig::enListadoPacientes());

        config(['tenant.facturacion_afip.modo' => FacturacionAfipConfig::MODO_MOVIMIENTO_CAJA]);
        $this->assertFalse(FacturacionAfipConfig::enListadoPacientes());
    }
}
