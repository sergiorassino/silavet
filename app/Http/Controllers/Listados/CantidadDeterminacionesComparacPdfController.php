<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Entorno\LabInstitucional;
use App\Support\Listados\CantidadDeterminacionesComparacConsulta;
use App\Support\Listados\CantidadDeterminacionesComparacFiltros;
use App\Support\Listados\CantidadDeterminacionesComparacTcpdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CantidadDeterminacionesComparacPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'cdc:pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $filtros = CantidadDeterminacionesComparacFiltros::desdeRequest($request);
        $filas = CantidadDeterminacionesComparacConsulta::comparativa($filtros);
        $resumen = CantidadDeterminacionesComparacConsulta::resumen($filas, $filtros);

        $pdf = CantidadDeterminacionesComparacTcpdf::generar([
            'header' => LabInstitucional::datosParaPdf(),
            'filas' => $filas->all(),
            'periodo1' => $resumen['periodo1'],
            'periodo2' => $resumen['periodo2'],
            'total1' => $resumen['total1'],
            'total2' => $resumen['total2'],
            'totalDiferencia' => $resumen['totalDiferencia'],
        ]);

        return CantidadDeterminacionesComparacTcpdf::respuestaHttp(
            $pdf,
            'cantidad-determinaciones-comparac.pdf',
        );
    }
}
