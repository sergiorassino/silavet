<?php

namespace Tests\Unit;

use App\Support\Precios\DescuentoPerfilesVolumenConsulta;
use PHPUnit\Framework\TestCase;

class DescuentoPerfilesVolumenConsultaTest extends TestCase
{
    public function test_porcentaje_por_cantidad_escalones(): void
    {
        $this->assertSame(0.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(0));
        $this->assertSame(0.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(24));
        $this->assertSame(2.5, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(25));
        $this->assertSame(2.5, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(49));
        $this->assertSame(5.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(50));
        $this->assertSame(5.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(99));
        $this->assertSame(8.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(100));
        $this->assertSame(8.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(149));
        $this->assertSame(10.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(150));
        $this->assertSame(10.0, DescuentoPerfilesVolumenConsulta::porcentajePorCantidad(500));
    }

    public function test_proximo_umbral(): void
    {
        $this->assertSame(
            ['faltan' => 25, 'porcentaje' => 2.5, 'umbral' => 25],
            DescuentoPerfilesVolumenConsulta::proximoUmbral(0)
        );
        $this->assertSame(
            ['faltan' => 22, 'porcentaje' => 5.0, 'umbral' => 50],
            DescuentoPerfilesVolumenConsulta::proximoUmbral(28)
        );
        $this->assertSame(
            ['faltan' => 1, 'porcentaje' => 8.0, 'umbral' => 100],
            DescuentoPerfilesVolumenConsulta::proximoUmbral(99)
        );
        $this->assertNull(DescuentoPerfilesVolumenConsulta::proximoUmbral(150));
        $this->assertNull(DescuentoPerfilesVolumenConsulta::proximoUmbral(200));
    }

    public function test_formatear_porcentaje(): void
    {
        $this->assertSame('5%', DescuentoPerfilesVolumenConsulta::formatearPorcentaje(5.0));
        $this->assertSame('2.5%', DescuentoPerfilesVolumenConsulta::formatearPorcentaje(2.5));
        $this->assertSame('10%', DescuentoPerfilesVolumenConsulta::formatearPorcentaje(10.0));
    }
}
