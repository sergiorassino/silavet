<?php

namespace Tests\Unit;

use App\Support\Resultados\ResultadosEstadosCatalog;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ResultadosEstadosCatalogTest extends TestCase
{
    public function test_flujo_de_cuatro_estados_incluye_final_env(): void
    {
        Config::set('tenant.protocolos.estados_flujo', 4);

        $this->assertTrue(ResultadosEstadosCatalog::usaFinalEnv());
        $this->assertSame(
            ['En Proc.', 'Parcial', 'Final', 'Final/Env'],
            ResultadosEstadosCatalog::valores()
        );
        $this->assertSame(
            ['Final', 'Final/Env'],
            ResultadosEstadosCatalog::estadosFinalizados()
        );
        $this->assertSame('Final/Env', ResultadosEstadosCatalog::siguiente('Final'));
        $this->assertSame('En Proc.', ResultadosEstadosCatalog::siguiente('Final/Env'));
    }

    public function test_flujo_de_tres_estados_omite_final_env(): void
    {
        Config::set('tenant.protocolos.estados_flujo', 3);

        $this->assertFalse(ResultadosEstadosCatalog::usaFinalEnv());
        $this->assertSame(
            ['En Proc.', 'Parcial', 'Final'],
            ResultadosEstadosCatalog::valores()
        );
        $this->assertSame(['Final'], ResultadosEstadosCatalog::estadosFinalizados());
        $this->assertFalse(ResultadosEstadosCatalog::esValido('Final/Env'));
        $this->assertSame('Final', ResultadosEstadosCatalog::normalizar('Final/Env'));
        $this->assertSame('En Proc.', ResultadosEstadosCatalog::siguiente('Final'));
        $this->assertSame('#66FFCC', ResultadosEstadosCatalog::colorDashboard('Final'));
        $this->assertSame('vl-pacientes-row--final-env', ResultadosEstadosCatalog::claseCssFila('Final'));
    }

    public function test_flujo_de_cuatro_estados_usa_rojo_para_final(): void
    {
        Config::set('tenant.protocolos.estados_flujo', 4);

        $this->assertSame('#ef4444', ResultadosEstadosCatalog::colorDashboard('Final'));
        $this->assertSame('vl-pacientes-row--final', ResultadosEstadosCatalog::claseCssFila('Final'));
        $this->assertSame('#66FFCC', ResultadosEstadosCatalog::colorDashboard('Final/Env'));
    }

    public function test_valor_invalido_en_config_usa_cuatro_estados(): void
    {
        Config::set('tenant.protocolos.estados_flujo', 99);

        $this->assertTrue(ResultadosEstadosCatalog::usaFinalEnv());
        $this->assertCount(4, ResultadosEstadosCatalog::valores());
    }
}
