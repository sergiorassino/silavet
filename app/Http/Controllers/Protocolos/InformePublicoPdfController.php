<?php

namespace App\Http\Controllers\Protocolos;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Support\Informes\InformePacienteConsulta;
use App\Support\Informes\InformePacienteTcpdf;
use App\Support\Security\OpaqueRouteToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Descarga pública del informe PDF — sin login, accesible por link de WhatsApp.
 *
 * Seguridad: token opaco cifrado con APP_KEY, TTL 30 días, sin ID numérico en URL.
 * Rate limit: 20 descargas/minuto por IP.
 */
class InformePublicoPdfController extends Controller
{
    public function __invoke(Request $request, string $ref): Response
    {
        $ip = $request->ip() ?? 'unknown';
        $key = 'informe-publico:'.$ip;

        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        $decoded = OpaqueRouteToken::decodeInformePublico($ref);
        if ($decoded === null) {
            abort(404);
        }

        $paciente = Paciente::query()
            ->where('idPacientes', $decoded['id'])
            ->first();

        if ($paciente === null) {
            abort(404);
        }

        $datos = InformePacienteConsulta::armar($paciente);
        if ($datos === null) {
            abort(404);
        }

        $pdf = InformePacienteTcpdf::generar($datos);
        $nombre = InformePacienteTcpdf::nombreArchivo($datos);

        return InformePacienteTcpdf::respuestaHttp($pdf, $nombre);
    }
}
