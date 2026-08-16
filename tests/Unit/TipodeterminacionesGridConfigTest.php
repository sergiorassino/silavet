<?php

namespace Tests\Unit;

use App\Support\Tipodeterminaciones\TipodeterminacionesGridConfig;
use Tests\TestCase;

class TipodeterminacionesGridConfigTest extends TestCase
{
    public function test_catalogo_con_columna_usa_derivacion(): void
    {
        config(['tenant.tipodeterminaciones.derivacion' => 'catalogo']);

        $this->assertSame('derivacion', TipodeterminacionesGridConfig::columnaFkCentro(true));
    }

    public function test_catalogo_sin_columna_cae_a_destino(): void
    {
        config(['tenant.tipodeterminaciones.derivacion' => 'catalogo']);

        $this->assertSame('destino', TipodeterminacionesGridConfig::columnaFkCentro(false));
    }

    public function test_si_no_usa_destino_aunque_exista_columna_derivacion(): void
    {
        config(['tenant.tipodeterminaciones.derivacion' => 'si_no']);

        $this->assertSame('destino', TipodeterminacionesGridConfig::columnaFkCentro(true));
    }
}
