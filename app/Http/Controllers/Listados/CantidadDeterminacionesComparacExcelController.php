<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Listados\CantidadDeterminacionesComparacConsulta;
use App\Support\Listados\CantidadDeterminacionesComparacExporter;
use App\Support\Listados\CantidadDeterminacionesComparacFiltros;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CantidadDeterminacionesComparacExcelController extends Controller
{
    public function __invoke(Request $request, CantidadDeterminacionesComparacExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'cdc:xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $filtros = CantidadDeterminacionesComparacFiltros::desdeRequest($request);
        $filas = CantidadDeterminacionesComparacConsulta::comparativa($filtros);

        if ($filas->isEmpty()) {
            abort(404);
        }

        $resumen = CantidadDeterminacionesComparacConsulta::resumen($filas, $filtros);
        $resultado = $exporter->buildXlsx($filas, $filtros, $resumen);

        return response()->streamDownload(
            fn () => $exporter->escribirEnSalida($resultado['spreadsheet']),
            $resultado['filename'],
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],
        );
    }
}
