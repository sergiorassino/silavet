<?php

namespace Tests\Unit;

use App\Support\Tesoreria\MovimientosCajaListadoFiltros;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MovimientosCajaListadoFiltrosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('tenant.tesoreria.implementacion', 'tesoreria_pacientes');
    }

    public function test_sanitizar_para_url_omite_fechas_de_hoy(): void
    {
        $hoy = now()->toDateString();

        $this->assertSame([], MovimientosCajaListadoFiltros::sanitizar([
            'fechaDesde' => $hoy,
            'fechaHasta' => $hoy,
            'page' => 1,
        ]));
    }

    public function test_sanitizar_para_url_conserva_rango_distinto_de_hoy(): void
    {
        $this->assertSame(
            [
                'fechaDesde' => '2024-03-01',
                'fechaHasta' => '2024-03-15',
                'page' => 3,
            ],
            MovimientosCajaListadoFiltros::sanitizar([
                'fechaDesde' => '2024-03-01',
                'fechaHasta' => '2024-03-15',
                'page' => 3,
            ])
        );
    }

    public function test_request_movimientos_pertenece_al_modulo_caja(): void
    {
        $request = Request::create('/tesoreria/movimientos', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'tesoreria/movimientos', static fn () => null);
            $route->name('tesoreria.movimientos.index');
            $route->bind($request);

            return $route;
        });

        $this->assertTrue(MovimientosCajaListadoFiltros::requestPerteneceAlModulo($request));
    }

    public function test_request_saldos_por_dia_no_pertenece_al_modulo(): void
    {
        $request = Request::create('/tesoreria/saldos-por-dia', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'tesoreria/saldos-por-dia', static fn () => null);
            $route->name('tesoreria.saldos-por-dia.index');
            $route->bind($request);

            return $route;
        });

        $this->assertFalse(MovimientosCajaListadoFiltros::requestPerteneceAlModulo($request));
    }
}
