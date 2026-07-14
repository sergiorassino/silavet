<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Entorno\LabInstitucional;
use App\Support\Listados\CantidadDeterminacionesComparacChartTcpdf;
use App\Support\Listados\CantidadDeterminacionesComparacConsulta;
use App\Support\Listados\CantidadDeterminacionesComparacFiltros;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CantidadDeterminacionesComparacChartPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'cdc:chart-pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'chartImage' => ['required', 'string', 'max:8000000'],
        ]);

        $filtros = CantidadDeterminacionesComparacFiltros::desdeRequest($request);
        $filas = CantidadDeterminacionesComparacConsulta::comparativa($filtros);
        $resumen = CantidadDeterminacionesComparacConsulta::resumen($filas, $filtros);

        $pdf = CantidadDeterminacionesComparacChartTcpdf::generar([
            'header' => LabInstitucional::datosParaPdf(),
            'periodo1' => $resumen['periodo1'],
            'periodo2' => $resumen['periodo2'],
            'chart_base64' => (string) $request->input('chartImage'),
        ]);

        return CantidadDeterminacionesComparacChartTcpdf::respuestaHttp(
            $pdf,
            'cantidad-determinaciones-comparac-grafico.pdf',
        );
    }
}
