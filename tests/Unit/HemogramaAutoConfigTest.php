<?php

namespace Tests\Unit;

use App\Support\Resultados\HemogramaAutoConfig;
use Tests\TestCase;

class HemogramaAutoConfigTest extends TestCase
{
    public function test_inactivo_por_defecto(): void
    {
        config(['tenant.hemograma_auto.activo' => false]);

        $this->assertFalse(HemogramaAutoConfig::activo());
    }

    public function test_items_omite_nulos_y_cero(): void
    {
        config([
            'tenant.hemograma_auto.items' => [
                'hto' => 3,
                'eritrocitos' => null,
                'hb' => 0,
                'serie_roja' => 209,
                'serie_blanca' => 210,
            ],
        ]);

        $this->assertSame([
            'hto' => 3,
            'serie_roja' => 209,
            'serie_blanca' => 210,
        ], HemogramaAutoConfig::items());
    }

    public function test_id_items_disparo_excluye_destinos(): void
    {
        config([
            'tenant.hemograma_auto.items' => [
                'hto' => 3,
                'plaquetas' => 18,
                'plaquetas_conteo_manual' => 239,
                'serie_roja' => 209,
                'serie_blanca' => 210,
            ],
        ]);

        $this->assertSame([3, 18, 239], HemogramaAutoConfig::idItemsDisparo());
    }
}
