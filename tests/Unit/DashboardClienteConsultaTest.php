<?php

namespace Tests\Unit;

use App\Support\Dashboard\DashboardClienteConsulta;
use Tests\TestCase;

class DashboardClienteConsultaTest extends TestCase
{
    public function test_texto_aviso_plano_elimina_html_y_colapsa_espacios(): void
    {
        $html = '<p>El informe del paciente  <b>Firulais</b>, (protocolo: 123) ha sido actualizdo</p>';

        $this->assertSame(
            'El informe del paciente Firulais, (protocolo: 123) ha sido actualizdo',
            DashboardClienteConsulta::textoAvisoPlano($html)
        );
    }

    public function test_texto_aviso_plano_vacio(): void
    {
        $this->assertSame('', DashboardClienteConsulta::textoAvisoPlano('   '));
    }
}
