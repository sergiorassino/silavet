<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Listados\ExcelPacientesConsulta;
use App\Support\Listados\ExcelPacientesExporter;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelPacientesExcelController extends Controller
{
    public function __invoke(Request $request, ExcelPacientesExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'ep:xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'fechaDesde' => ['required', 'date'],
            'fechaHasta' => ['required', 'date'],
        ], [
            'fechaDesde.required' => 'Indique la fecha inicial.',
            'fechaHasta.required' => 'Indique la fecha final.',
            'fechaDesde.date' => 'La fecha inicial no es válida.',
            'fechaHasta.date' => 'La fecha final no es válida.',
        ]);

        $fechaDesde = trim((string) $validated['fechaDesde']);
        $fechaHasta = trim((string) $validated['fechaHasta']);

        if ($fechaDesde > $fechaHasta) {
            throw ValidationException::withMessages([
                'fechaDesde' => 'La fecha inicial no puede ser posterior a la final.',
            ]);
        }

        $filtros = [
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ];

        $filas = ExcelPacientesConsulta::listado($filtros);
        $resultado = $exporter->buildXlsx($filas, $filtros);

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
