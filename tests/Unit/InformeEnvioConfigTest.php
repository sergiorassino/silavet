<?php

namespace Tests\Unit;

use App\Support\Envio\InformeEnvioConfig;
use App\Support\Envio\InformeEnvioServicio;
use Tests\TestCase;

class InformeEnvioConfigTest extends TestCase
{
    private function aplicar(array $flags): void
    {
        config(['tenant.envio_informes' => array_replace([
            'destinatario_cliente' => true,
            'destinatario_paciente' => true,
            'forma_mail' => true,
            'forma_whatsapp' => true,
        ], $flags)]);
    }

    public function test_default_ambos_destinos_y_ambas_formas(): void
    {
        $this->aplicar([]);

        $this->assertTrue(InformeEnvioConfig::permiteDestinatarioCliente());
        $this->assertTrue(InformeEnvioConfig::permiteDestinatarioPaciente());
        $this->assertTrue(InformeEnvioConfig::permiteFormaMail());
        $this->assertTrue(InformeEnvioConfig::permiteFormaWhatsapp());
        $this->assertNull(InformeEnvioConfig::destinatarioUnico());
        $this->assertNull(InformeEnvioConfig::formaUnica());
    }

    public function test_solo_cliente_oculta_paciente(): void
    {
        $this->aplicar(['destinatario_paciente' => false]);

        $this->assertSame([InformeEnvioServicio::DEST_CLIENTE], InformeEnvioConfig::destinatariosHabilitados());
        $this->assertSame(InformeEnvioServicio::DEST_CLIENTE, InformeEnvioConfig::destinatarioUnico());
        $this->assertFalse(InformeEnvioConfig::permiteDestinatarioPaciente());
    }

    public function test_solo_mail_oculta_whatsapp(): void
    {
        $this->aplicar(['forma_whatsapp' => false]);

        $this->assertSame([InformeEnvioServicio::FORMA_MAIL], InformeEnvioConfig::formasHabilitadas());
        $this->assertSame(InformeEnvioServicio::FORMA_MAIL, InformeEnvioConfig::formaUnica());
        $this->assertFalse(InformeEnvioConfig::permiteFormaWhatsapp());
    }

    public function test_ambos_destinos_false_cae_a_cliente(): void
    {
        $this->aplicar([
            'destinatario_cliente' => false,
            'destinatario_paciente' => false,
        ]);

        $this->assertTrue(InformeEnvioConfig::permiteDestinatarioCliente());
        $this->assertFalse(InformeEnvioConfig::permiteDestinatarioPaciente());
    }

    public function test_ambas_formas_false_cae_a_mail(): void
    {
        $this->aplicar([
            'forma_mail' => false,
            'forma_whatsapp' => false,
        ]);

        $this->assertTrue(InformeEnvioConfig::permiteFormaMail());
        $this->assertFalse(InformeEnvioConfig::permiteFormaWhatsapp());
    }

    public function test_epizoolab_solo_cliente_y_mail(): void
    {
        /** @var array<string, mixed> $overrides */
        $overrides = require config_path('tenants/epizoolab.php');

        $this->assertArrayHasKey('envio_informes', $overrides);
        $this->aplicar($overrides['envio_informes']);

        $this->assertTrue(InformeEnvioConfig::permiteDestinatarioCliente());
        $this->assertFalse(InformeEnvioConfig::permiteDestinatarioPaciente());
        $this->assertTrue(InformeEnvioConfig::permiteFormaMail());
        $this->assertFalse(InformeEnvioConfig::permiteFormaWhatsapp());
        $this->assertSame(InformeEnvioServicio::DEST_CLIENTE, InformeEnvioConfig::destinatarioUnico());
        $this->assertSame(InformeEnvioServicio::FORMA_MAIL, InformeEnvioConfig::formaUnica());
    }
}
