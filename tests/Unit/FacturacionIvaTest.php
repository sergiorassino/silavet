<?php

namespace Tests\Unit;

use App\Support\Facturacion\FacturacionAfipConfig;
use App\Support\Facturacion\FacturacionIva;
use RuntimeException;
use Tests\TestCase;

class FacturacionIvaTest extends TestCase
{
    public function test_precio_con_iva_incluido_abre_neto_e_iva(): void
    {
        config([
            'tenant.facturacion_afip.alicuota_iva' => 21,
            'tenant.facturacion_afip.precio_incluye_iva' => true,
        ]);

        $iva = FacturacionIva::desglosar(121000);

        $this->assertSame(100000.0, $iva['neto']);
        $this->assertSame(21000.0, $iva['iva']);
        $this->assertSame(121000.0, $iva['total']);
        $this->assertSame(5, $iva['alic_id']);
        $this->assertSame(21.0, $iva['alicuota']);
    }

    public function test_precio_neto_suma_iva_al_total(): void
    {
        config([
            'tenant.facturacion_afip.alicuota_iva' => 21,
            'tenant.facturacion_afip.precio_incluye_iva' => false,
        ]);

        $iva = FacturacionIva::desglosar(100000);

        $this->assertSame(100000.0, $iva['neto']);
        $this->assertSame(21000.0, $iva['iva']);
        $this->assertSame(121000.0, $iva['total']);
    }

    public function test_responsable_inscripto_con_cuit_es_factura_a_y_el_resto_factura_b(): void
    {
        $this->assertSame(1, FacturacionIva::tipoFactura(FacturacionIva::CONDICION_RI));
        $this->assertSame(6, FacturacionIva::tipoFactura(FacturacionIva::CONDICION_MONOTRIBUTO));
        $this->assertSame(6, FacturacionIva::tipoFactura(FacturacionIva::CONDICION_CONSUMIDOR_FINAL));
        $this->assertSame(6, FacturacionIva::tipoFactura(FacturacionIva::CONDICION_EXENTO));
        $this->assertSame(3, FacturacionIva::tipoNotaCredito(1));
        $this->assertSame(8, FacturacionIva::tipoNotaCredito(6));
        $this->assertSame(12, FacturacionIva::tipoNotaCredito(11));
    }

    public function test_alicuota_desconocida_falla(): void
    {
        config([
            'tenant.facturacion_afip.alicuota_iva' => 19,
            'tenant.facturacion_afip.precio_incluye_iva' => true,
        ]);

        $this->expectException(RuntimeException::class);
        FacturacionIva::desglosar(100);
    }

    public function test_regimen_por_defecto_es_monotributo(): void
    {
        config(['tenant.facturacion_afip.regimen' => 'cualquier-cosa']);
        $this->assertTrue(FacturacionAfipConfig::esMonotributo());
        $this->assertFalse(FacturacionAfipConfig::esResponsableInscripto());

        config(['tenant.facturacion_afip.regimen' => FacturacionAfipConfig::REGIMEN_RESPONSABLE_INSCRIPTO]);
        $this->assertTrue(FacturacionAfipConfig::esResponsableInscripto());
        $this->assertSame(21.0, FacturacionAfipConfig::alicuotaIva());
        $this->assertTrue(FacturacionAfipConfig::precioIncluyeIva());
    }
}
