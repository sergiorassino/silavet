<?php

namespace Tests\Unit;

use App\Support\Informes\InformeGrupoReferencias;
use Tests\TestCase;

class InformeGrupoReferenciasTest extends TestCase
{
    public function test_observaciones_nunca_muestra_encabezado(): void
    {
        $this->assertFalse(InformeGrupoReferencias::mostrarEncabezado('OBSERVACIONES', 1));
        $this->assertFalse(InformeGrupoReferencias::mostrarEncabezado('Observaciones', 1));
        $this->assertFalse(InformeGrupoReferencias::mostrarEncabezado('observaciones', 0));
    }

    public function test_flag_1_muestra_encabezado(): void
    {
        $this->assertTrue(InformeGrupoReferencias::mostrarEncabezado('Hemograma', 1));
        $this->assertTrue(InformeGrupoReferencias::mostrarEncabezado('INFORME DE ECOGRAFÍA', 1));
    }

    public function test_flag_0_oculta_encabezado(): void
    {
        $this->assertFalse(InformeGrupoReferencias::mostrarEncabezado('Hemograma', 0));
        $this->assertFalse(InformeGrupoReferencias::mostrarEncabezado('INFORME DE ECOGRAFÍA', 0));
    }
}
