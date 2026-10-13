<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Support\Informes\InformePacienteConsulta;
use App\Support\Informes\InformePacienteTcpdf;
use App\Support\Security\OpaqueRouteToken;
use App\Support\Tutor\TutorContext;
use App\Support\Tutor\TutorPacientesConsulta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class InformeTutorPdfController extends Controller
{
    public function __invoke(Request $request, string $ref): Response
    {
        $ctx = TutorContext::fromSession();
        abort_unless($ctx->isValid(), 403);

        $decoded = OpaqueRouteToken::decodeInformeTutor($ref);
        $esperado = hash('sha256', $ctx->dni);
        if ($decoded === null
            || strlen($decoded['h']) !== strlen($esperado)
            || ! hash_equals($esperado, $decoded['h'])) {
            abort(404);
        }

        $key = 'tutor-informe-pdf:'.$decoded['h'];
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Demasiadas solicitudes de informe. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        $paciente = TutorPacientesConsulta::enAlcance($decoded['id'], $ctx->dni);
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
