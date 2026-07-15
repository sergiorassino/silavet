<?php

namespace Tests\Unit;

use App\Auth\UsuarioUserProvider;
use App\Models\Usuario;
use App\Support\UsuarioMenuPortal;
use Tests\TestCase;

class UsuarioUserProviderTest extends TestCase
{
    public function test_verifica_contraseña_en_texto_plano(): void
    {
        $usuario = new Usuario();
        $usuario->password = '1234';

        $this->assertTrue(UsuarioUserProvider::verificarPassword($usuario, '1234'));
        $this->assertFalse(UsuarioUserProvider::verificarPassword($usuario, '0000'));
    }

    public function test_staff_con_id_clientes_legacy_puede_autenticarse(): void
    {
        if (! $this->app['config']->get('database.connections.mysql.database')) {
            $this->markTestSkipped('Requiere MySQL configurado.');
        }

        $provider = new UsuarioUserProvider();
        $user = $provider->retrieveByCredentials([
            'dni' => '13964667',
            'portal' => 'staff',
        ]);

        if ($user === null) {
            $this->markTestSkipped('Usuario de prueba 13964667 no existe en esta BD.');
        }

        $this->assertInstanceOf(Usuario::class, $user);
        $this->assertTrue(UsuarioUserProvider::verificarPassword($user, '2161'));
        $this->assertFalse(UsuarioMenuPortal::esAdministracion($user->idRoles));
        $this->assertFalse(UsuarioMenuPortal::esCliente($user->idRoles, $user->idClientes));
        $this->assertSame('dashboard', UsuarioMenuPortal::rutaInicio($user->idRoles, $user->idClientes));
    }

    public function test_usuario_cliente_no_ingresa_por_portal_staff(): void
    {
        if (! $this->app['config']->get('database.connections.mysql.database')) {
            $this->markTestSkipped('Requiere MySQL configurado.');
        }

        $provider = new UsuarioUserProvider();
        $cliente = $provider->retrieveByCredentials([
            'dni' => 'maria',
            'portal' => 'staff',
        ]);

        $this->assertNull($cliente);
    }

    public function test_login_unificado_recupera_usuario_cliente(): void
    {
        if (! $this->app['config']->get('database.connections.mysql.database')) {
            $this->markTestSkipped('Requiere MySQL configurado.');
        }

        $provider = new UsuarioUserProvider();
        $cliente = $provider->retrieveByCredentials([
            'dni' => 'maria',
        ]);

        if ($cliente === null) {
            $this->markTestSkipped('Usuario de prueba maria no existe en esta BD.');
        }

        $this->assertInstanceOf(Usuario::class, $cliente);
        $this->assertTrue(UsuarioMenuPortal::esCliente($cliente->idRoles, $cliente->idClientes));
        $this->assertSame('cliente.home', UsuarioMenuPortal::rutaInicio($cliente->idRoles, $cliente->idClientes));
    }

    public function test_cliente_id_1_no_es_autogestion(): void
    {
        $this->assertFalse(UsuarioMenuPortal::esCliente(null, UsuarioMenuPortal::ID_CLIENTES_LABORATORIO));
        $this->assertFalse(UsuarioMenuPortal::esCliente(2, UsuarioMenuPortal::ID_CLIENTES_LABORATORIO));
        $this->assertSame('dashboard', UsuarioMenuPortal::rutaInicio(2, UsuarioMenuPortal::ID_CLIENTES_LABORATORIO));

        // ALQU: clientes veterinarios suelen tener idRoles null e idClientes distinto de 1
        $this->assertTrue(UsuarioMenuPortal::esCliente(null, 4));
        $this->assertSame('cliente.home', UsuarioMenuPortal::rutaInicio(null, 4));
        $this->assertTrue(UsuarioMenuPortal::esCliente(1, 5));
        $this->assertFalse(UsuarioMenuPortal::esCliente(2, null));
        $this->assertFalse(UsuarioMenuPortal::esCliente(null, null));
    }
}
