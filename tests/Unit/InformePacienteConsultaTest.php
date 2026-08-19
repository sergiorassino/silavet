<?php

namespace Tests\Unit;

use App\Support\Informes\InformePacienteConsulta;
use Tests\TestCase;

class InformePacienteConsultaTest extends TestCase
{
    public function test_texto_informe_conserva_menor_que_en_texto_clinico(): void
    {
        $texto = 'Se obtienen muestras de lesión solitaria (< 2 cm) nodular.';

        $this->assertSame($texto, InformePacienteConsulta::textoInforme($texto));
    }

    public function test_texto_informe_conserva_menor_que_sin_cierre(): void
    {
        $texto = 'Lesión solitaria (< 2 cm de diámetro, no ulcerada.';

        $this->assertSame($texto, InformePacienteConsulta::textoInforme($texto));
    }

    public function test_texto_informe_convierte_br_y_quita_etiquetas(): void
    {
        $html = 'Línea 1<br>Línea 2<b>negrita</b>';

        $this->assertSame("Línea 1\nLínea 2negrita", InformePacienteConsulta::textoInforme($html));
    }
}
