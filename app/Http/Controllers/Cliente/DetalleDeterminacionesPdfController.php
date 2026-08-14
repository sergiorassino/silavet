<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Support\Cliente\DetalleDeterminacionesConsulta;
use App\Support\Cliente\DetalleDeterminacionesTcpdf;
use App\Support\Entorno\LabInstitucional;
use App\Support\PermisosIaCatalog;
use App\Support\Security\OpaqueRouteToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class DetalleDeterminacionesPdfController extends Controller
{
    public function __invoke(Request $request, string $ref): Response
    {
        abort_unless(labCtx()->esCliente() && labCtx()->idClientes, 403);
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $decoded = OpaqueRouteToken::decodePacienteDeterminacionesCliente($ref);
        if ($decoded === null) {
            abort(404);
        }

        $uid = (int) (auth()->id() ?? 0);
        if ($decoded['u'] !== $uid) {
            abort(404);
        }

        $key = 'cliente-det-determ-pdf:'.$uid;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        $paciente = DetalleDeterminacionesConsulta::pacienteEnAlcance($decoded['id']);
        if ($paciente === null) {
            abort(404);
        }

        $datos = DetalleDeterminacionesConsulta::armar($paciente);
        $datos['header'] = LabInstitucional::datosParaPdf();

        $pdf = DetalleDeterminacionesTcpdf::generar($datos);
        $nombre = DetalleDeterminacionesTcpdf::nombreArchivo($datos);

        return DetalleDeterminacionesTcpdf::respuestaHttp($pdf, $nombre);
    }
}
