<?php

namespace App\Support\Envio;

/**
 * Destinatarios y canales del modal «Enviar informe», por tenant.
 *
 * Defaults (ambos destinos y ambas formas): `config/tenant.php`.
 * Un laboratorio declara `false` solo para lo que no usa
 * (`config/tenants/{slug}.php`).
 *
 * Si ambos destinos (o ambas formas) quedan en false, se cae a cliente / mail
 * para no dejar el modal vacío.
 *
 * Ver docs/modulos/envio-informes.md.
 */
final class InformeEnvioConfig
{
    /**
     * @return list<string>
     */
    public static function destinatariosHabilitados(): array
    {
        $out = [];
        if ((bool) config('tenant.envio_informes.destinatario_cliente', true)) {
            $out[] = InformeEnvioServicio::DEST_CLIENTE;
        }
        if ((bool) config('tenant.envio_informes.destinatario_paciente', true)) {
            $out[] = InformeEnvioServicio::DEST_PACIENTE;
        }

        return $out !== [] ? $out : [InformeEnvioServicio::DEST_CLIENTE];
    }

    /**
     * @return list<string>
     */
    public static function formasHabilitadas(): array
    {
        $out = [];
        if ((bool) config('tenant.envio_informes.forma_mail', true)) {
            $out[] = InformeEnvioServicio::FORMA_MAIL;
        }
        if ((bool) config('tenant.envio_informes.forma_whatsapp', true)) {
            $out[] = InformeEnvioServicio::FORMA_WHATSAPP;
        }

        return $out !== [] ? $out : [InformeEnvioServicio::FORMA_MAIL];
    }

    public static function permiteDestinatarioCliente(): bool
    {
        return self::permiteDestinatario(InformeEnvioServicio::DEST_CLIENTE);
    }

    public static function permiteDestinatarioPaciente(): bool
    {
        return self::permiteDestinatario(InformeEnvioServicio::DEST_PACIENTE);
    }

    public static function permiteFormaMail(): bool
    {
        return self::permiteForma(InformeEnvioServicio::FORMA_MAIL);
    }

    public static function permiteFormaWhatsapp(): bool
    {
        return self::permiteForma(InformeEnvioServicio::FORMA_WHATSAPP);
    }

    public static function permiteDestinatario(string $destinatario): bool
    {
        return in_array($destinatario, self::destinatariosHabilitados(), true);
    }

    public static function permiteForma(string $forma): bool
    {
        return in_array($forma, self::formasHabilitadas(), true);
    }

    /** Único destinatario habilitado, o null si hay más de uno. */
    public static function destinatarioUnico(): ?string
    {
        $destinos = self::destinatariosHabilitados();

        return count($destinos) === 1 ? $destinos[0] : null;
    }

    /** Única forma habilitada, o null si hay más de una. */
    public static function formaUnica(): ?string
    {
        $formas = self::formasHabilitadas();

        return count($formas) === 1 ? $formas[0] : null;
    }
}
