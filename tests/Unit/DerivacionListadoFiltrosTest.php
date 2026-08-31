<?php

namespace Tests\Unit;

use App\Livewire\Protocolos\DerivacionIndex;
use App\Support\Protocolos\DerivacionListadoFiltros;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class DerivacionListadoFiltrosTest extends TestCase
{
    public function test_sanitizar_para_url_omite_centro_y_sin_finalizados(): void
    {
        $this->assertSame([], DerivacionListadoFiltros::sanitizar([
            'agrupacion' => DerivacionIndex::AGRUPACION_CENTRO,
            'incluirFinalizados' => false,
            'page' => 1,
        ]));
    }

    public function test_sanitizar_para_url_conserva_agrupacion_no_default_y_finalizados(): void
    {
        $this->assertSame(
            [
                'agrupacion' => DerivacionIndex::AGRUPACION_FECHA,
                'incluirFinalizados' => 1,
                'page' => 2,
            ],
            DerivacionListadoFiltros::sanitizar([
                'agrupacion' => DerivacionIndex::AGRUPACION_FECHA,
                'incluirFinalizados' => true,
                'page' => 2,
            ])
        );
    }

    public function test_sanitizar_sin_omitir_defaults_conserva_centro(): void
    {
        $this->assertSame(
            ['agrupacion' => DerivacionIndex::AGRUPACION_CENTRO],
            DerivacionListadoFiltros::sanitizar([
                'agrupacion' => DerivacionIndex::AGRUPACION_CENTRO,
            ], omitirDefaults: false)
        );
    }

    public function test_combinar_la_query_pisa_la_sesion(): void
    {
        $combinado = DerivacionListadoFiltros::combinar(
            [
                'agrupacion' => DerivacionIndex::AGRUPACION_CLIENTE,
                'incluirFinalizados' => 1,
                'page' => 4,
            ],
            [
                'agrupacion' => DerivacionIndex::AGRUPACION_NINGUNA,
            ]
        );

        $this->assertSame(DerivacionIndex::AGRUPACION_NINGUNA, $combinado['agrupacion']);
        $this->assertSame(1, $combinado['incluirFinalizados']);
        $this->assertSame(4, $combinado['page']);
    }

    public function test_request_de_derivaciones_pertenece_al_modulo(): void
    {
        $request = Request::create('/derivaciones', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'derivaciones', static fn () => null);
            $route->name('derivaciones.index');
            $route->bind($request);

            return $route;
        });

        $this->assertTrue(DerivacionListadoFiltros::requestPerteneceAlModulo($request));
    }

    public function test_request_resultados_con_origen_derivaciones_pertenece_al_modulo(): void
    {
        $request = Request::create('/protocolos/1/resultados?origen=derivaciones', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'protocolos/{id}/resultados', static fn () => null);
            $route->name('protocolos.resultados');
            $route->bind($request);

            return $route;
        });

        $this->assertTrue(DerivacionListadoFiltros::requestPerteneceAlModulo($request));
    }

    public function test_request_de_pacientes_no_pertenece_al_modulo_derivaciones(): void
    {
        $request = Request::create('/protocolos', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'protocolos', static fn () => null);
            $route->name('protocolos.index');
            $route->bind($request);

            return $route;
        });

        $this->assertFalse(DerivacionListadoFiltros::requestPerteneceAlModulo($request));
    }
}
