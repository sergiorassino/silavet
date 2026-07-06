<?php

namespace Tests\Unit;

use App\Support\ProtocoloNumero;
use App\Support\ProtocoloNumero\ProtocoloNumeroContext;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ProtocoloNumeroTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('tenant.protocolos.implementacion', 'fecha_diaria');
    }

    public function test_previsualizar_anual_consecutivo_usa_formato_aa_mas_cinco_digitos(): void
    {
        Config::set('tenant.protocolos.implementacion', 'anual_consecutivo');

        $numero = ProtocoloNumero::previsualizarParaFecha('2026-07-06');

        $this->assertMatchesRegularExpression('/^\d{7}$/', $numero);
        $this->assertStringStartsWith('26', $numero);
        $this->assertSame('2600001', $numero);
    }

    public function test_previsualizar_fecha_diaria_usa_formato_yymmdd_mas_tres_digitos(): void
    {
        $numero = ProtocoloNumero::previsualizarParaFecha('2026-07-06');

        $this->assertMatchesRegularExpression('/^\d{9}$/', $numero);
        $this->assertStringStartsWith('260706', $numero);
    }

    public function test_previsualizar_dual_largo_usa_formato_yymmdd_mas_tres_digitos(): void
    {
        Config::set('tenant.protocolos.implementacion', 'dual_corto_largo');

        $numero = ProtocoloNumero::previsualizar(
            ProtocoloNumeroContext::fromFecha('2026-07-06', 'L')
        );

        $this->assertMatchesRegularExpression('/^\d{9}$/', $numero);
        $this->assertStringStartsWith('260706', $numero);
    }

    public function test_previsualizar_dual_corto_usa_prefijo_c_y_nueve_digitos(): void
    {
        Config::set('tenant.protocolos.implementacion', 'dual_corto_largo');

        $numero = ProtocoloNumero::previsualizar(
            ProtocoloNumeroContext::fromFecha('2026-07-06', 'C')
        );

        $this->assertMatchesRegularExpression('/^C\d{9}$/', $numero);
        $this->assertStringStartsWith('C000000101', $numero);
    }

    public function test_usa_tipo_protocolo_solo_en_implementacion_dual(): void
    {
        Config::set('tenant.protocolos.implementacion', 'fecha_diaria');
        $this->assertFalse(ProtocoloNumero::usaTipoProtocolo());

        Config::set('tenant.protocolos.implementacion', 'dual_corto_largo');
        $this->assertTrue(ProtocoloNumero::usaTipoProtocolo());
    }
}
