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
}
