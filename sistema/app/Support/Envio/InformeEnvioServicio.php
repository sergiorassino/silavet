<?php

namespace App\Support\Envio;

use App\Mail\InformeProtocoloMail;
use App\Models\Entorno;
use App\Models\Paciente;
use App\Support\Entorno\LabInstitucional;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class InformeEnvioServicio
{
    public const DEST_CLIENTE = 'cliente';

    public const DEST_PACIENTE = 'paciente';

    public const FORMA_MAIL = 'mail';

    public const FORMA_WHATSAPP = 'whatsapp';

    /**
     * @return array{
     *     cliente_email: string,
     *     cliente_whatsapp: string,
     *     paciente_email: string,
     *     paciente_whatsapp: string,
     *     protocolo: string,
     *     nombre: string,
     *     cliente_nombre: string,
     * }
     */
    public static function contactos(Paciente $paciente): array
    {
        $cliente = $paciente->cliente;

        return [
            'cliente_email' => trim((string) ($cliente?->email ?? '')),
            'cliente_whatsapp' => trim((string) ($cliente?->whatsapp ?? '')),
            'paciente_email' => trim((string) ($paciente->email ?? '')),
            'paciente_whatsapp' => trim((string) ($paciente->whatsapp ?? '')),
            'protocolo' => trim((string) ($paciente->nombreProtocolo ?? '')),
            'nombre' => trim((string) ($paciente->nombre ?? '')),
            'cliente_nombre' => trim((string) ($cliente?->nombre ?? '')),
        ];
    }

    public static function emailDestino(array $contactos, string $destinatario): string
    {
        return $destinatario === self::DEST_CLIENTE
            ? $contactos['cliente_email']
            : $contactos['paciente_email'];
    }

    public static function whatsappDestino(array $contactos, string $destinatario): string
    {
        return $destinatario === self::DEST_CLIENTE
            ? $contactos['cliente_whatsapp']
            : $contactos['paciente_whatsapp'];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public static function enviarMail(Paciente $paciente, string $destinatario): array
    {
        $contactos = self::contactos($paciente);
        $email = self::emailDestino($contactos, $destinatario);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'error' => 'El destinatario no tiene un email válido.'];
        }

        $entorno = self::entornoMail();
        if ($entorno === null) {
            return ['ok' => false, 'error' => 'No hay configuración de envío de mail en Parámetros del Sistema.'];
        }

        $cta = trim((string) ($entorno->ctaEnvioMail ?? ''));
        $pass = (string) ($entorno->passEnvioMail ?? '');
        $from = trim((string) ($entorno->fromMail ?? ''));

        if ($from === '' || filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'error' => 'Configure el remitente (From) en Parámetros del Sistema.'];
        }

        if ($cta === '' || $pass === '') {
            return ['ok' => false, 'error' => 'Configure la cuenta y contraseña de envío en Parámetros del Sistema.'];
        }

        $fromName = trim((string) ($entorno->nombrePieMail ?? ''));
        if ($fromName === '') {
            $fromName = LabInstitucional::datos()['nombre'];
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.username', $cta);
        Config::set('mail.mailers.smtp.password', $pass);
        Config::set('mail.from.address', $from);
        Config::set('mail.from.name', $fromName);

        try {
            app('mail.manager')->purge('smtp');
            Mail::mailer('smtp')->to($email)->send(new InformeProtocoloMail($paciente, $entorno, $contactos));
        } catch (Throwable $e) {
            report($e);

            return [
                'ok' => false,
                'error' => 'No se pudo enviar el mail. Verifique SMTP (.env) y la cuenta de envío en Parámetros.',
            ];
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: true, url: string}|array{ok: false, error: string}
     */
    public static function urlWhatsappWeb(Paciente $paciente, string $destinatario): array
    {
        $contactos = self::contactos($paciente);
        $whatsapp = self::whatsappDestino($contactos, $destinatario);
        $telefono = self::normalizarTelefonoWhatsapp($whatsapp);

        if ($telefono === null) {
            return ['ok' => false, 'error' => 'El destinatario no tiene un WhatsApp válido.'];
        }

        $lab = LabInstitucional::datos()['nombre'];
        $protocolo = $contactos['protocolo'] !== '' ? $contactos['protocolo'] : '—';
        $nombre = $contactos['nombre'] !== '' ? $contactos['nombre'] : '—';

        $texto = "Hola, le enviamos información del protocolo {$protocolo}"
            ." (paciente: {$nombre}) desde {$lab}.";

        $url = 'https://wa.me/'.$telefono.'?text='.rawurlencode($texto);

        return ['ok' => true, 'url' => $url];
    }

    public static function normalizarTelefonoWhatsapp(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $digits = ltrim($digits, '0');

        if ($digits === '' || strlen($digits) < 8) {
            return null;
        }

        // Números locales AR (área + celular, tip. 10 dígitos): anteponer 54.
        if (! str_starts_with($digits, '54') && strlen($digits) <= 10) {
            $digits = '54'.$digits;
        }

        return $digits;
    }

    private static function entornoMail(): ?Entorno
    {
        if (! Schema::hasTable('entorno')) {
            return null;
        }

        return Entorno::query()->find(1);
    }
}
