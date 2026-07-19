<?php

namespace App\Http\Controllers\Protocolos;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Support\PermisosIaCatalog;
use App\Support\Protocolos\EtiquetasTuboTcpdf;
use App\Support\Security\OpaqueRouteToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class EtiquetasTuboPdfController extends Controller
{
    public function __invoke(Request $request, string $ref): Response
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $decoded = OpaqueRouteToken::decodeEtiquetasTubo($ref);
        if ($decoded === null) {
            abort(404);
        }

        $uid = (int) (auth()->id() ?? 0);
        if ($decoded['u'] !== $uid) {
            abort(404);
        }

        $key = 'protocolos-etiquetas-pdf:'.$uid;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Demasiadas solicitudes de etiquetas. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        $paciente = $this->pacienteEnAlcance($decoded['id']);
        if ($paciente === null || $paciente->esPagoGlobal()) {
            abort(404);
        }

        $cfg = EtiquetasTuboTcpdf::configDesdeEntorno();
        $etiquetas = EtiquetasTuboTcpdf::armarEtiquetas($paciente, $decoded['c'], $cfg);
        $pdf = EtiquetasTuboTcpdf::generar($etiquetas, $cfg);
        $nombre = EtiquetasTuboTcpdf::nombreArchivo($paciente);

        return EtiquetasTuboTcpdf::respuestaHttp($pdf, $nombre);
    }

    private function pacienteEnAlcance(int $id): ?Paciente
    {
        $ctx = labCtx();

        return Paciente::query()
            ->with('cliente')
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('pacientes.idClientes', $ctx->idClientes);
            })
            ->where('pacientes.idPacientes', $id)
            ->first();
    }
}
