<?php

namespace App\Mail;

use App\Models\Entorno;
use App\Models\Paciente;
use App\Support\Entorno\LabInstitucional;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InformeProtocoloMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     cliente_email: string,
     *     cliente_whatsapp: string,
     *     paciente_email: string,
     *     paciente_whatsapp: string,
     *     protocolo: string,
     *     nombre: string,
     *     cliente_nombre: string,
     * }  $contactos
     */
    public function __construct(
        public Paciente $paciente,
        public Entorno $entorno,
        public array $contactos,
    ) {}

    public function envelope(): Envelope
    {
        $from = trim((string) ($this->entorno->fromMail ?? ''));
        $fromName = trim((string) ($this->entorno->nombrePieMail ?? ''));
        if ($fromName === '') {
            $fromName = LabInstitucional::datos()['nombre'];
        }

        $protocolo = $this->contactos['protocolo'] !== ''
            ? $this->contactos['protocolo']
            : (string) $this->paciente->idPacientes;

        return new Envelope(
            from: new Address($from, $fromName),
            subject: 'Informe de laboratorio — Protocolo '.$protocolo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.informe-protocolo',
            with: [
                'paciente' => $this->paciente,
                'contactos' => $this->contactos,
                'lab' => LabInstitucional::datos(),
                'pie' => [
                    'nombre' => trim((string) ($this->entorno->nombrePieMail ?? '')),
                    'direccion' => trim((string) ($this->entorno->direccionPieMail ?? '')),
                    'telefono' => trim((string) ($this->entorno->telefonoPieMail ?? '')),
                    'email' => trim((string) ($this->entorno->emailPieMail ?? '')),
                ],
            ],
        );
    }
}
