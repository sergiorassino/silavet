<?php

namespace App\Http\Controllers\Protocolos;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Support\Informes\InformePacienteConsulta;
use App\Support\Informes\InformePacienteTcpdf;
use App\Support\PermisosIaCatalog;
use App\Support\Security\OpaqueRouteToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class InformePacientePdfController extends Controller
{
    public function __invoke(Request $request, string $ref): Response
    {
        abort_unless(tienePermiso(PermisosIaCatalog::INFORMES), 403);

        $decoded = OpaqueRouteToken::decodeInformePaciente($ref);
        if ($decoded === null) {
            abort(404);
        }

        $uid = (int) (auth()->id() ?? 0);
        if ($decoded['u'] !== $uid) {
            abort(404);
        }

        $key = 'protocolos-informe-pdf:'.$uid;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Demasiadas solicitudes de informe. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        $paciente = $this->pacienteEnAlcance($decoded['id']);
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

    private function pacienteEnAlcance(int $id): ?Paciente
    {
        $ctx = labCtx();

        return Paciente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('pacientes.idClientes', $ctx->idClientes);
            })
            ->where('pacientes.idPacientes', $id)
            ->first();
    }
}
