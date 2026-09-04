<?php

namespace App\Support\Envio;

use App\Mail\InformeProtocoloMail;
use App\Models\Entorno;
use App\Models\Paciente;
use App\Support\EmailList;
use App\Support\Entorno\LabInstitucional;
use App\Support\Informes\InformePacienteConsulta;
use App\Support\Informes\InformePacienteTcpdf;
use App\Support\Security\OpaqueRouteToken;
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

    /**
     * Destinos de mail: el cliente admite varios (separados por ; o ,);
     * el paciente (propietario) sigue siendo uno solo.
     *
     * @return list<string>
     */
    public static function emailsDestino(array $contactos, string $destinatario): array
    {
        $raw = self::emailDestino($contactos, $destinatario);

        if ($destinatario === self::DEST_CLIENTE) {
            return EmailList::parse($raw);
        }

        $email = trim($raw);

        return $email !== '' ? [$email] : [];
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
    /**
     * @return array{ok: true, enviados: int}|array{ok: false, error: string}
     */
    public static function enviarMail(Paciente $paciente, string $destinatario): array
    {
        $contactos = self::contactos($paciente);
        $emails = self::emailsDestino($contactos, $destinatario);

        $validos = [];
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $validos[] = $email;
            }
        }

        if ($validos === []) {
            return ['ok' => false, 'error' => 'El destinatario no tiene un email válido.'];
        }

        $entorno = self::entornoMail();
        if ($entorno === null) {
            return ['ok' => false, 'error' => 'No hay configuración de envío de mail en Parámetros del Sistema.'];
        }

        $cta = trim((string) ($entorno->ctaEnvioMail ?? ''));
        // Gmail muestra la app password como "xxxx xxxx xxxx xxxx"; SMTP espera 16 chars sin espacios.
        $pass = preg_replace('/\s+/', '', (string) ($entorno->passEnvioMail ?? '')) ?? '';
        $from = self::direccionRemitente($entorno);
        $fromName = self::nombreRemitente($entorno);

        if ($cta === '' || $pass === '') {
            return ['ok' => false, 'error' => 'Configure la cuenta y contraseña de envío en Parámetros del Sistema.'];
        }

        if ($from === '') {
            return ['ok' => false, 'error' => 'Configure la cuenta de envío (email válido) en Parámetros del Sistema.'];
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.username', $cta);
        Config::set('mail.mailers.smtp.password', $pass);
        Config::set('mail.from.address', $from);
        Config::set('mail.from.name', $fromName);

        $host = trim((string) config('mail.mailers.smtp.host', ''));
        $port = (int) config('mail.mailers.smtp.port', 0);

        // Generar el PDF antes de conectar con SMTP; si falla, error visible sin enviar mail vacío.
        $datosPdf = InformePacienteConsulta::armar($paciente);
        if ($datosPdf === null) {
            return ['ok' => false, 'error' => 'No se pudo armar el informe PDF. Verifique que el protocolo tenga datos completos.'];
        }

        try {
            $pdfObj = InformePacienteTcpdf::generar($datosPdf);
            $pdfNombre = InformePacienteTcpdf::nombreArchivo($datosPdf);
            $pdfBinario = $pdfObj->Output($pdfNombre, 'S');
        } catch (Throwable $ePdf) {
            report($ePdf);

            return ['ok' => false, 'error' => 'Error al generar el PDF del informe: '.mb_substr(trim($ePdf->getMessage()), 0, 150)];
        }

        $enviados = 0;
        $fallidos = [];
        $ultimoError = '';

        try {
            app('mail.manager')->purge('smtp');
        } catch (Throwable $ePurge) {
            report($ePurge);
        }

        foreach ($validos as $email) {
            try {
                Mail::mailer('smtp')->to($email)->send(
                    new InformeProtocoloMail($paciente, $entorno, $contactos, $pdfBinario, $pdfNombre)
                );
                $enviados++;
            } catch (Throwable $e) {
                report($e);
                $fallidos[] = $email;
                $ultimoError = self::mensajeErrorSmtp($e);
            }
        }

        if ($enviados === 0) {
            $servidor = $host !== '' ? $host.($port > 0 ? ":{$port}" : '') : '(MAIL_HOST vacío)';

            return [
                'ok' => false,
                'error' => 'No se pudo enviar el mail'
                    .($ultimoError !== '' ? ': '.$ultimoError : '.')
                    .' La cuenta va en Parámetros (entorno); el servidor SMTP en .env'
                    ." (MAIL_HOST/MAIL_PORT/MAIL_ENCRYPTION → {$servidor}).",
            ];
        }

        if ($fallidos !== []) {
            return [
                'ok' => false,
                'error' => 'Se envió a '.$enviados.' destinatario(s), pero falló en: '
                    .implode(', ', $fallidos)
                    .($ultimoError !== '' ? ' ('.$ultimoError.')' : ''),
            ];
        }

        return ['ok' => true, 'enviados' => $enviados];
    }

    /**
     * Dirección From: email en fromMail, o la cuenta de envío si fromMail es un nombre.
     */
    public static function direccionRemitente(Entorno $entorno): string
    {
        $from = trim((string) ($entorno->fromMail ?? ''));
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL) !== false) {
            return $from;
        }

        $cta = trim((string) ($entorno->ctaEnvioMail ?? ''));
        if ($cta !== '' && filter_var($cta, FILTER_VALIDATE_EMAIL) !== false) {
            return $cta;
        }

        return '';
    }

    /**
     * Nombre visible del remitente: fromMail si no es email, si no nombrePieMail / lab.
     */
    public static function nombreRemitente(Entorno $entorno): string
    {
        $from = trim((string) ($entorno->fromMail ?? ''));
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            return $from;
        }

        $nombre = trim((string) ($entorno->nombrePieMail ?? ''));
        if ($nombre !== '') {
            return $nombre;
        }

        return LabInstitucional::datos()['nombre'];
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

        $lab = LabInstitucional::datos();
        $nombre = $contactos['nombre'] !== '' ? $contactos['nombre'] : '—';
        $propietario = trim((string) ($paciente->propietario ?? ''));

        // URL pública del informe (token opaco, sin login, válido 30 días).
        $ref = OpaqueRouteToken::forInformePublico((int) $paciente->idPacientes);
        $urlInforme = route('protocolos.informe-publico', ['ref' => $ref]);

        // Datos de firma: usar pie de mail si están cargados, si no los del laboratorio emisor.
        $entorno = self::entornoMail();
        $firmaNombre = $entorno !== null ? trim((string) ($entorno->nombrePieMail ?? '')) : '';
        $firmaDireccion = $entorno !== null ? trim((string) ($entorno->direccionPieMail ?? '')) : '';
        $firmaTelefono = $entorno !== null ? trim((string) ($entorno->telefonoPieMail ?? '')) : '';

        if ($firmaNombre === '') {
            $firmaNombre = $lab['nombre'];
        }
        if ($firmaDireccion === '') {
            $firmaDireccion = $lab['direccion'];
        }
        if ($firmaTelefono === '') {
            $firmaTelefono = $lab['telefono'];
        }

        // Saludo: laboratorio/persona de destino del WhatsApp (no el laboratorio emisor).
        $saludoNombre = $destinatario === self::DEST_CLIENTE
            ? $contactos['cliente_nombre']
            : ($propietario !== '' ? $propietario : $contactos['nombre']);
        if ($saludoNombre === '') {
            $saludoNombre = '—';
        }

        $texto = "Hola, {$saludoNombre}\n";
        $texto .= "Ya se encuentran disponibles los resultados de los análisis de:\n";
        $texto .= "Paciente: {$nombre}";
        if ($propietario !== '') {
            $texto .= ", Propietario: {$propietario}";
        }
        $texto .= "\n\n";
        $texto .= "Puede visualizarlos haciendo click en el siguiente link "
            ."(o copiando y pegando el link en un navegador):\n";
        $texto .= "{$urlInforme}\n\n";
        $texto .= "Atte.\nSaludos cordiales.\n";
        $texto .= $firmaNombre;
        if ($firmaDireccion !== '') {
            $texto .= "\n{$firmaDireccion}";
        }
        if ($firmaTelefono !== '') {
            $texto .= "\nWhatsApp {$firmaTelefono}";
        }

        $url = 'https://web.whatsapp.com/send?phone='.$telefono.'&text='.rawurlencode($texto);

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

    /**
     * Resume el fallo SMTP para el usuario, sin volcar credenciales ni stack.
     */
    private static function mensajeErrorSmtp(Throwable $e): string
    {
        $msg = trim($e->getMessage());
        if ($msg === '') {
            return '';
        }

        // Evitar strings enormes o con passwords en querystrings.
        $msg = preg_replace('/password=[^&\s]+/i', 'password=***', $msg) ?? $msg;
        $msg = preg_replace('/\s+/', ' ', $msg) ?? $msg;

        if (mb_strlen($msg) > 220) {
            $msg = mb_substr($msg, 0, 217).'…';
        }

        return $msg;
    }
}
