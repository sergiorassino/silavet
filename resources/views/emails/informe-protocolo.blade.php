<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe de laboratorio</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f7;font-family:Arial,Helvetica,sans-serif;color:#2C3333;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f7;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #d4e4e4;">
                <tr>
                    <td style="background:#2D6A6A;color:#ffffff;padding:20px 24px;">
                        <p style="margin:0;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.85;">Laboratorio</p>
                        <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;">{{ $lab['nombre'] }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px;font-size:15px;line-height:1.5;">
                            Le informamos los datos del protocolo de laboratorio:
                        </p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
                            <tr>
                                <td style="padding:4px 0;color:#5E8A8A;width:140px;">Protocolo</td>
                                <td style="padding:4px 0;font-weight:700;">{{ $contactos['protocolo'] !== '' ? $contactos['protocolo'] : '—' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#5E8A8A;">Paciente</td>
                                <td style="padding:4px 0;">{{ $contactos['nombre'] !== '' ? $contactos['nombre'] : '—' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#5E8A8A;">Cliente</td>
                                <td style="padding:4px 0;">{{ $contactos['cliente_nombre'] !== '' ? $contactos['cliente_nombre'] : '—' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#5E8A8A;">Fecha</td>
                                <td style="padding:4px 0;">{{ $paciente->fechhoyFormateada() }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#5E8A8A;">Estado</td>
                                <td style="padding:4px 0;">{{ $paciente->estado ?: '—' }}</td>
                            </tr>
                        </table>
                        <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#5E8A8A;">
                            Ante cualquier consulta, responda a este correo o comuníquese con el laboratorio.
                        </p>
                    </td>
                </tr>
                @if ($pie['nombre'] !== '' || $pie['direccion'] !== '' || $pie['telefono'] !== '' || $pie['email'] !== '')
                    <tr>
                        <td style="padding:16px 24px 20px;border-top:1px solid #d4e4e4;background:#f8fbfb;font-size:12px;line-height:1.5;color:#5E8A8A;">
                            @if ($pie['nombre'] !== '')
                                <strong style="color:#2C3333;">{{ $pie['nombre'] }}</strong><br>
                            @endif
                            @if ($pie['direccion'] !== '')
                                {{ $pie['direccion'] }}<br>
                            @endif
                            @if ($pie['telefono'] !== '')
                                Tel: {{ $pie['telefono'] }}<br>
                            @endif
                            @if ($pie['email'] !== '')
                                {{ $pie['email'] }}
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
</body>
</html>
