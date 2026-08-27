<?php

namespace Tests\Unit;

use App\Support\Tesoreria\PagoGlobalRegistro;
use App\Support\Tesoreria\TesoreriaConfig;
use Tests\TestCase;

class TesoreriaConfigTest extends TestCase
{
    public function test_mostrar_modulo_true_por_defecto(): void
    {
        config(['tenant.tesoreria.mostrar_modulo' => true]);

        $this->assertTrue(TesoreriaConfig::mostrarModulo());
    }

    public function test_mostrar_modulo_false_solo_si_se_declara(): void
    {
        config(['tenant.tesoreria.mostrar_modulo' => false]);

        $this->assertFalse(TesoreriaConfig::mostrarModulo());
    }

    public function test_pago_global_deshabilitado_por_defecto(): void
    {
        config([
            'tenant.tesoreria.pago_global' => false,
            'tenant.tesoreria.implementacion' => TesoreriaConfig::IMPL_MOVIMIENTOS,
        ]);

        $this->assertFalse(TesoreriaConfig::pagoGlobalHabilitado());
    }

    public function test_pago_global_requiere_flag_y_variante_movimientos(): void
    {
        config([
            'tenant.tesoreria.pago_global' => true,
            'tenant.tesoreria.implementacion' => TesoreriaConfig::IMPL_MOVIMIENTOS,
        ]);

        $this->assertTrue(TesoreriaConfig::pagoGlobalHabilitado());
    }

    public function test_pago_global_ignorado_en_tesoreria_pacientes(): void
    {
        config([
            'tenant.tesoreria.pago_global' => true,
            'tenant.tesoreria.implementacion' => TesoreriaConfig::IMPL_PACIENTES,
        ]);

        $this->assertFalse(TesoreriaConfig::pagoGlobalHabilitado());
    }

    public function test_columna_pagado_deshabilitada_por_defecto(): void
    {
        config(['tenant.tesoreria.columna_pagado' => false]);

        $this->assertFalse(TesoreriaConfig::columnaPagadoHabilitada());
    }

    public function test_columna_pagado_independiente_de_afip_y_variante(): void
    {
        config([
            'tenant.tesoreria.columna_pagado' => true,
            'tenant.tesoreria.implementacion' => TesoreriaConfig::IMPL_PACIENTES,
            'tenant.facturacion_afip.habilitado' => false,
        ]);

        $this->assertTrue(TesoreriaConfig::columnaPagadoHabilitada());
    }

    public function test_normalizar_importe_formato_ar(): void
    {
        $this->assertSame('1234.56', PagoGlobalRegistro::normalizarImporte('1.234,56'));
        $this->assertSame('1234.56', PagoGlobalRegistro::normalizarImporte('1234,56'));
        $this->assertSame('1234.56', PagoGlobalRegistro::normalizarImporte('1234.56'));
    }
}
