<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Paciente;
use App\Models\Tipodeterminacion;
use App\Support\Precios\ListaPreciosConfig;
use App\Support\Precios\PrecioDeterminacionResolver;
use Tests\TestCase;

class ListaPreciosConfigTest extends TestCase
{
    public function test_default_es_cliente(): void
    {
        config(['tenant.precios.lista' => null]);

        $this->assertTrue(ListaPreciosConfig::esPorCliente());
        $this->assertFalse(ListaPreciosConfig::esPorPaciente());
    }

    public function test_modo_invalido_y_legacy_fija_1_caen_a_cliente(): void
    {
        config(['tenant.precios.lista' => 'otra']);
        $this->assertSame(ListaPreciosConfig::MODO_CLIENTE, ListaPreciosConfig::implementacion());

        config(['tenant.precios.lista' => 'fija_1']);
        $this->assertSame(ListaPreciosConfig::MODO_CLIENTE, ListaPreciosConfig::implementacion());
    }

    public function test_normaliza_enteros_y_legacy_l_punto(): void
    {
        $this->assertSame(1, ListaPreciosConfig::normalizar(null));
        $this->assertSame(1, ListaPreciosConfig::normalizar(''));
        $this->assertSame(1, ListaPreciosConfig::normalizar(1));
        $this->assertSame(2, ListaPreciosConfig::normalizar(2));
        $this->assertSame(3, ListaPreciosConfig::normalizar('3'));
        $this->assertSame(1, ListaPreciosConfig::normalizar('L.1'));
        $this->assertSame(2, ListaPreciosConfig::normalizar('l.2'));
        $this->assertSame(3, ListaPreciosConfig::normalizar('L3'));
        $this->assertSame(1, ListaPreciosConfig::normalizar(99));
        $this->assertSame(1, ListaPreciosConfig::normalizar(0));
    }

    public function test_etiqueta_l_n(): void
    {
        $this->assertSame('L.1', ListaPreciosConfig::etiqueta(1));
        $this->assertSame('L.2', ListaPreciosConfig::etiqueta(2));
        $this->assertSame('L.1', ListaPreciosConfig::etiqueta(9));
    }

    public function test_nro_para_paciente_modo_cliente_usa_lista_del_cliente(): void
    {
        config(['tenant.precios.lista' => ListaPreciosConfig::MODO_CLIENTE]);

        $cliente = new Cliente();
        $cliente->setRawAttributes([ListaPreciosConfig::COLUMNA_CLIENTE => 3]);

        $paciente = new Paciente();
        $paciente->setRawAttributes([ListaPreciosConfig::COLUMNA_PACIENTE => 2]);
        $paciente->setRelation('cliente', $cliente);

        $this->assertSame(3, ListaPreciosConfig::nroParaPaciente($paciente));
    }

    public function test_nro_para_paciente_modo_paciente(): void
    {
        config(['tenant.precios.lista' => ListaPreciosConfig::MODO_PACIENTE]);

        $paciente = new Paciente();
        $paciente->setRawAttributes([ListaPreciosConfig::COLUMNA_PACIENTE => 'L.2']);

        $this->assertSame(2, ListaPreciosConfig::nroParaPaciente($paciente));
        $this->assertSame('L.2', ListaPreciosConfig::etiquetaParaPaciente($paciente));
    }

    public function test_resolver_elige_precio2_y_precio3(): void
    {
        $tipo = new Tipodeterminacion();
        $tipo->setRawAttributes([
            'precio' => 100.5,
            'precio2' => 80,
            'precio3' => 60.25,
        ]);

        $this->assertSame(100.5, PrecioDeterminacionResolver::resolverPrecioLista($tipo, 1));
        $this->assertSame(80.0, PrecioDeterminacionResolver::resolverPrecioLista($tipo, 2));
        $this->assertSame(60.25, PrecioDeterminacionResolver::resolverPrecioLista($tipo, 3));
    }

    public function test_resolver_sin_precio2_cae_a_lista_1(): void
    {
        $tipo = new Tipodeterminacion();
        $tipo->setRawAttributes(['precio' => 50]);

        $this->assertSame(50.0, PrecioDeterminacionResolver::resolverPrecioLista($tipo, 2));
        $this->assertSame(50.0, PrecioDeterminacionResolver::resolverPrecioLista1($tipo));
    }

    public function test_resolver_para_paciente_usa_alcance(): void
    {
        config(['tenant.precios.lista' => ListaPreciosConfig::MODO_PACIENTE]);

        $tipo = new Tipodeterminacion();
        $tipo->setRawAttributes([
            'precio' => 100,
            'precio2' => 70,
            'precio3' => 40,
        ]);

        $paciente = new Paciente();
        $paciente->setRawAttributes([ListaPreciosConfig::COLUMNA_PACIENTE => 2]);

        $this->assertSame(70.0, PrecioDeterminacionResolver::resolverPrecioListaParaPaciente($tipo, $paciente));
    }
}
