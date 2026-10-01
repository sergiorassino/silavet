<?php

namespace Tests\Unit;

use App\Support\Listados\ClientesResumenMensualConsulta;
use PHPUnit\Framework\TestCase;

class ClientesResumenMensualDescuentoTest extends TestCase
{
    public function test_el_10_porciento_se_aplica_sobre_el_total_de_neto_sin_iva(): void
    {
        $cd = ClientesResumenMensualConsulta::desgloseDescuentoSobreTotalNeto(97520.68, 10);

        $this->assertSame(87768.61, $cd['sin_iva']);
        $this->assertSame(18431.41, $cd['iva']);
        $this->assertSame(106200.02, $cd['con_iva']);
    }
}
