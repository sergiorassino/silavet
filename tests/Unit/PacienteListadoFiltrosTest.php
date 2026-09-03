<?php

namespace Tests\Unit;

use App\Livewire\Protocolos\PacienteIndex;
use App\Support\Protocolos\PacienteListadoFiltros;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class PacienteListadoFiltrosTest extends TestCase
{
    public function test_sanitizar_para_url_omite_hoy_y_fecha_de_hoy(): void
    {
        $hoy = now()->toDateString();

        $this->assertSame([], PacienteListadoFiltros::sanitizar([
            'vista' => PacienteIndex::VISTA_HOY,
            'fechaVista' => $hoy,
            'page' => 1,
        ]));
    }

    public function test_sanitizar_para_url_conserva_historial_y_rango(): void
    {
        $this->assertSame(
            [
                'vista' => PacienteIndex::VISTA_HISTORIAL,
                'fechaDesde' => '2024-06-01',
                'fechaHasta' => '2024-06-30',
                'page' => 3,
            ],
            PacienteListadoFiltros::sanitizar([
                'vista' => PacienteIndex::VISTA_HISTORIAL,
                'fechaDesde' => '2024-06-01',
                'fechaHasta' => '2024-06-30',
                'page' => 3,
            ])
        );
    }

    public function test_sanitizar_omite_fechas_de_historial_invalidas(): void
    {
        $this->assertSame(
            ['vista' => PacienteIndex::VISTA_HISTORIAL],
            PacienteListadoFiltros::sanitizar([
                'vista' => PacienteIndex::VISTA_HISTORIAL,
                'fechaDesde' => '01/06/2024',
                'fechaHasta' => '',
            ])
        );
    }

    public function test_sanitizar_sin_omitir_defaults_conserva_vista_hoy(): void
    {
        $hoy = now()->toDateString();

        $this->assertSame(
            [
                'vista' => PacienteIndex::VISTA_HOY,
                'fechaVista' => $hoy,
            ],
            PacienteListadoFiltros::sanitizar([
                'vista' => PacienteIndex::VISTA_HOY,
                'fechaVista' => $hoy,
            ], omitirDefaults: false)
        );
    }

    public function test_sanitizar_conserva_busqueda_no_vacia(): void
    {
        $this->assertSame(
            ['busqueda' => '12345'],
            PacienteListadoFiltros::sanitizar(['busqueda' => '  12345  '])
        );
    }

    public function test_sanitizar_omite_busqueda_vacia(): void
    {
        $this->assertSame([], PacienteListadoFiltros::sanitizar(['busqueda' => '   ']));
    }

    public function test_combinar_la_query_pisa_la_sesion(): void
    {
        $combinado = PacienteListadoFiltros::combinar(
            [
                'vista' => PacienteIndex::VISTA_HISTORIAL,
                'fechaVista' => '2020-01-15',
                'filtroEstado' => PacienteIndex::FILTRO_PENDIENTES,
            ],
            [
                'vista' => PacienteIndex::VISTA_HOY,
            ]
        );

        $this->assertSame(PacienteIndex::VISTA_HOY, $combinado['vista']);
        $this->assertSame('2020-01-15', $combinado['fechaVista']);
        $this->assertSame(PacienteIndex::FILTRO_PENDIENTES, $combinado['filtroEstado']);
    }

    public function test_combinar_conserva_rango_de_historial(): void
    {
        $combinado = PacienteListadoFiltros::combinar(
            [
                'vista' => PacienteIndex::VISTA_HISTORIAL,
                'fechaDesde' => '2024-01-01',
                'fechaHasta' => '2024-01-31',
            ],
            [
                'vista' => PacienteIndex::VISTA_HISTORIAL,
                'fechaHasta' => '2024-02-15',
            ]
        );

        $this->assertSame(PacienteIndex::VISTA_HISTORIAL, $combinado['vista']);
        $this->assertSame('2024-01-01', $combinado['fechaDesde']);
        $this->assertSame('2024-02-15', $combinado['fechaHasta']);
    }

    public function test_request_de_protocolos_pertenece_al_modulo(): void
    {
        $request = Request::create('/protocolos', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'protocolos', static fn () => null);
            $route->name('protocolos.resultados');
            $route->bind($request);

            return $route;
        });

        $this->assertTrue(PacienteListadoFiltros::requestPerteneceAlModulo($request));
    }

    public function test_request_de_otra_seccion_no_pertenece_al_modulo(): void
    {
        $request = Request::create('/tesoreria/movimientos', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], 'tesoreria/movimientos', static fn () => null);
            $route->name('tesoreria.movimientos.index');
            $route->bind($request);

            return $route;
        });

        $this->assertFalse(PacienteListadoFiltros::requestPerteneceAlModulo($request));
    }

    public function test_request_livewire_no_cierra_el_modulo(): void
    {
        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('X-Livewire', 'true');

        $this->assertTrue(PacienteListadoFiltros::requestPerteneceAlModulo($request));
    }
}
