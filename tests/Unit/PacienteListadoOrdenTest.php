<?php

namespace Tests\Unit;

use App\Models\Paciente;
use App\Support\Protocolos\PacienteListadoOrden;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PacienteListadoOrdenTest extends TestCase
{
    public function test_sin_parametrizar_usa_orden_historico_con_fecha_y_tipo_registro(): void
    {
        Config::set('tenant.protocolos.orden_listado', []);

        $this->assertSame([], PacienteListadoOrden::criterios());

        $orders = PacienteListadoOrden::aplicar(Paciente::query())->getQuery()->orders;

        $this->assertNotEmpty($orders);
        $this->assertArrayHasKey('sql', $orders[0]);
        $this->assertStringContainsString('fechhoy', $orders[0]['sql']);
        $this->assertSame('pacientes.tipoRegistro', $orders[1]['column'] ?? null);
        $this->assertSame('pacientes.nombreProtocolo', $orders[3]['column'] ?? null);
        $this->assertSame('asc', $orders[3]['direction'] ?? null);
    }

    public function test_epizoolab_ordena_fechhoy_desc_luego_nombre_protocolo_desc(): void
    {
        $overrides = require config_path('tenants/epizoolab.php');
        Config::set('tenant.protocolos.orden_listado', $overrides['protocolos']['orden_listado']);

        $this->assertSame([
            ['fechhoy', 'desc'],
            ['nombreProtocolo', 'desc'],
        ], PacienteListadoOrden::criterios());

        $orders = PacienteListadoOrden::aplicar(Paciente::query())->getQuery()->orders;

        $this->assertSame([
            ['column' => 'pacientes.fechhoy', 'direction' => 'desc'],
            ['column' => 'pacientes.nombreProtocolo', 'direction' => 'desc'],
            ['column' => 'pacientes.idPacientes', 'direction' => 'desc'],
        ], $orders);
    }

    public function test_permite_fechhoy_desc_y_nombre_protocolo_desc(): void
    {
        Config::set('tenant.protocolos.orden_listado', [
            'fechhoy' => 'desc',
            'nombreProtocolo' => 'desc',
        ]);

        $orders = PacienteListadoOrden::aplicar(Paciente::query())->getQuery()->orders;

        $this->assertSame('pacientes.fechhoy', $orders[0]['column'] ?? null);
        $this->assertSame('desc', $orders[0]['direction'] ?? null);
        $this->assertSame('pacientes.nombreProtocolo', $orders[1]['column'] ?? null);
        $this->assertSame('desc', $orders[1]['direction'] ?? null);
    }

    public function test_alias_fecha_se_resuelve_a_fechhoy(): void
    {
        Config::set('tenant.protocolos.orden_listado', [
            'fecha' => 'desc',
            'nombreProtocolo' => 'asc',
        ]);

        $this->assertSame([
            ['fechhoy', 'desc'],
            ['nombreProtocolo', 'asc'],
        ], PacienteListadoOrden::criterios());
    }

    public function test_ignora_campos_no_permitidos(): void
    {
        Config::set('tenant.protocolos.orden_listado', [
            'fechhoy' => 'desc',
            'otro' => 'asc',
        ]);

        $this->assertSame([
            ['fechhoy', 'desc'],
        ], PacienteListadoOrden::criterios());
    }
}
