<?php

namespace Tests\Unit;

use App\Support\Pdf\TcpdfTextoSuperindice;
use Tests\TestCase;

class TcpdfTextoSuperindiceTest extends TestCase
{
    public function test_texto_sin_superindice_no_cambia(): void
    {
        $texto = 'Lesión solitaria (< 2 cm) nodular.';

        $this->assertNull(TcpdfTextoSuperindice::htmlSiHaceFalta($texto));
        $this->assertSame($texto, TcpdfTextoSuperindice::paraMedir($texto));
    }

    public function test_convierte_exponente_unicode_y_escapa_comparaciones(): void
    {
        $texto = "No hiperadrenocorticismo: <15 ×10⁻⁶\nHiperadrenocorticismo: >40 ×10⁻⁶";

        $this->assertSame(
            'No hiperadrenocorticismo: &lt;15 ×10<sup>-6</sup><br>Hiperadrenocorticismo: &gt;40 ×10<sup>-6</sup>',
            TcpdfTextoSuperindice::htmlSiHaceFalta($texto)
        );
        $this->assertSame(
            "No hiperadrenocorticismo: <15 ×10-6\nHiperadrenocorticismo: >40 ×10-6",
            TcpdfTextoSuperindice::paraMedir($texto)
        );
    }

    public function test_conserva_el_doble_espacio_entre_valor_y_unidad(): void
    {
        $this->assertSame(
            '14,7&nbsp;&nbsp;×10<sup>-6</sup>',
            TcpdfTextoSuperindice::htmlSiHaceFalta('14,7  ×10⁻⁶')
        );
    }

    public function test_acepta_etiqueta_sup_y_entidades_numericas(): void
    {
        $this->assertSame('×10<sup>-6</sup>', TcpdfTextoSuperindice::htmlSiHaceFalta('×10<sup>-6</sup>'));
        $this->assertSame('×10<sup>-6</sup>', TcpdfTextoSuperindice::htmlSiHaceFalta('×10&#8315;&#8310;'));
        $this->assertSame('×10-6', TcpdfTextoSuperindice::paraMedir('×10<sup>-6</sup>'));
    }

    public function test_convierte_subindice(): void
    {
        $this->assertSame('H<sub>2</sub>O', TcpdfTextoSuperindice::htmlSiHaceFalta('H₂O'));
    }
}
