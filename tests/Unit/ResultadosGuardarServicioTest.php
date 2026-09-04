<?php

namespace Tests\Unit;

use App\Support\Resultados\ResultadosGuardarServicio;
use Tests\TestCase;

class ResultadosGuardarServicioTest extends TestCase
{
    public function test_tope_varchar_100_es_el_default_legacy(): void
    {
        $this->assertSame(
            100,
            ResultadosGuardarServicio::topeValor2DesdeMeta(['type' => 'varchar(100)'])
        );
        $this->assertSame(
            ResultadosGuardarServicio::VALOR2_TOPE_DEFAULT,
            ResultadosGuardarServicio::topeValor2DesdeMeta(null)
        );
    }

    public function test_tope_sigue_la_columna_agrandada_del_lab(): void
    {
        $this->assertSame(
            500,
            ResultadosGuardarServicio::topeValor2DesdeMeta(['type' => 'varchar(500)'])
        );
    }

    public function test_tope_text_usa_el_limite_de_text(): void
    {
        $this->assertSame(
            65535,
            ResultadosGuardarServicio::topeValor2DesdeMeta(['type_name' => 'text'])
        );
    }
}
