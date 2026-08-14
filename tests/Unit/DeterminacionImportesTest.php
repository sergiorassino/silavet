<?php

namespace Tests\Unit;

use App\Support\Precios\DeterminacionImportes;
use PHPUnit\Framework\TestCase;

class DeterminacionImportesTest extends TestCase
{
    public function test_modelo_nuevo_neto_y_precio(): void
    {
        $importes = DeterminacionImportes::resolver(14725.0, 13950.0, 775.0);

        $this->assertSame(14725.0, $importes['neto']);
        $this->assertSame(775.0, $importes['descuento']);
        $this->assertSame(13950.0, $importes['precio']);
    }

    public function test_legacy_precio_era_lista(): void
    {
        $importes = DeterminacionImportes::resolver(0.0, 14725.0, 775.0);

        $this->assertSame(14725.0, $importes['neto']);
        $this->assertSame(775.0, $importes['descuento']);
        $this->assertSame(13950.0, $importes['precio']);
    }

    public function test_recalcula_precio_si_falta(): void
    {
        $importes = DeterminacionImportes::resolver(2800.0, 0.0, 0.0);

        $this->assertSame(2800.0, $importes['neto']);
        $this->assertSame(0.0, $importes['descuento']);
        $this->assertSame(2800.0, $importes['precio']);
    }
}
