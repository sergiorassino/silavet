<?php

namespace App\Http\Controllers\Protocolos;

use App\Http\Controllers\Controller;
use App\Support\PermisosIaCatalog;
use App\Support\Protocolos\HojaRutaHemogramaConfig;
use App\Support\Protocolos\HojaRutaHemogramaConsulta;
use App\Support\Protocolos\HojaRutaHemogramaTcpdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class HojaRutaHemogramaPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);
        abort_if(labCtx()->esCliente(), 403);
        abort_unless(HojaRutaHemogramaConfig::activo(), 404);

        $uid = (int) (auth()->id() ?? 0);
        $key = 'protocolos-hoja-ruta-pdf:'.$uid;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Demasiadas solicitudes de hoja de ruta. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $fecha = HojaRutaHemogramaConsulta::normalizarFecha((string) ($validated['fecha'] ?? ''));
        $datos = HojaRutaHemogramaConsulta::paraFecha($fecha);
        $pdf = HojaRutaHemogramaTcpdf::generar($datos);
        $nombre = HojaRutaHemogramaTcpdf::nombreArchivo($fecha);

        return HojaRutaHemogramaTcpdf::respuestaHttp($pdf, $nombre);
    }
}
