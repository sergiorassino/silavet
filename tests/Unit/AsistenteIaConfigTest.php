<?php

namespace Tests\Unit;

use App\Support\Protocolos\AsistenteIaConfig;
use Tests\TestCase;

class AsistenteIaConfigTest extends TestCase
{
    public function test_default_muestra_la_columna(): void
    {
        config(['tenant.asistente_ia.mostrar_columna' => true]);

        $this->assertTrue(AsistenteIaConfig::mostrarColumna());
    }

    public function test_flag_en_false_oculta_la_columna(): void
    {
        config(['tenant.asistente_ia.mostrar_columna' => false]);

        $this->assertFalse(AsistenteIaConfig::mostrarColumna());
    }

    public function test_neolab_declara_la_columna_oculta(): void
    {
        $override = require base_path('config/tenants/neolab.php');

        $this->assertFalse($override['asistente_ia']['mostrar_columna']);
    }
}
