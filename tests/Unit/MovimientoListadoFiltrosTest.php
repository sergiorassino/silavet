<?php

namespace Tests\Unit;

use App\Livewire\Tesoreria\MovimientoIndex;
use App\Support\Tesoreria\MovimientoListadoFiltros;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MovimientoListadoFiltrosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('tenant.tesoreria.implementacion', 'tesoreria_movimientos');
    }

    public function test_sanitizar_para_url_omite_hoy_y_todos(): void
    {
        $this->assertSame([], MovimientoListadoFiltros::sanitizar([
            'vista' => MovimientoIndex::VISTA_HOY,
            'filtroTipo' => MovimientoIndex::FILTRO_TIPO_TODOS,
            'page' => 1,
        ]));
    }

    public function test_sanitizar_para_url_conserva_historial_ingreso_y_fechas(): void
    {
        $this->assertSame(
            [
                'vista' => MovimientoIndex::VISTA_HISTORIAL,
                'filtroTipo' => MovimientoIndex::FILTRO_TIPO_INGRESO,
                'fechaDesde' => '2024-06-01',
                'fechaHasta' => '2024-06-30',
                'page' => 2,
            ],
            MovimientoListadoFiltros::sanitizar([
                'vista' => MovimientoIndex::VISTA_HISTORIAL,
                'filtroTipo' => MovimientoIndex::FILTRO_TIPO_INGRESO,
                'fechaDesde' => '2024-06-01',
                'fechaHasta' => '2024-06-30',
                'page' => 2,
            ])
        );
    }

    public function test_combinar_la_query_pisa_la_sesion(): void
    {
        $combinado = MovimientoListadoFiltros::combinar(
            [
                'vista' => MovimientoIndex::VISTA_HISTORIAL,
                'filtroTipo' => MovimientoIndex::FILTRO_TIPO_EGRESO,
                'fechaDesde' => '2024-01-01',
            ],
            [
                'vista' => MovimientoIndex::VISTA_HOY,
            ]
        );

        $this->assertSame(MovimientoIndex::VISTA_HOY, $combinado['vista']);
        $this->assertSame(MovimientoIndex::FILTRO_TIPO_EGRESO, $combinado['filtroTipo']);
        $this->assertSame('2024-01-01', $combinado['fechaDesde']);
    }

    public function test_request_movimientos_pertenece_al_modulo(): void
    {
        $request = Request::create('/tesoreria/movimientos', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'tesoreria/movimientos', static fn () => null);
            $route->name('tesoreria.movimientos.index');
            $route->bind($request);

            return $route;
        });

        $this->assertTrue(MovimientoListadoFiltros::requestPerteneceAlModulo($request));
    }

    public function test_request_transferencias_no_pertenece_al_modulo(): void
    {
        $request = Request::create('/tesoreria/transferencias', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'tesoreria/transferencias', static fn () => null);
            $route->name('tesoreria.transferencias.index');
            $route->bind($request);

            return $route;
        });

        $this->assertFalse(MovimientoListadoFiltros::requestPerteneceAlModulo($request));
    }
}
