<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Listados\DeterminacionesPorClienteConsulta;
use App\Support\Listados\DeterminacionesPorClienteExporter;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeterminacionesPorClienteExcelController extends Controller
{
    public function __invoke(Request $request, DeterminacionesPorClienteExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'dpc:xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'idClientes' => ['nullable', 'integer'],
            'busqueda' => ['nullable', 'string', 'max:120'],
            'fechaDesde' => ['nullable', 'date'],
            'fechaHasta' => ['nullable', 'date'],
        ]);

        $ctx = labCtx();
        $idClientes = isset($validated['idClientes']) ? (int) $validated['idClientes'] : null;
        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $filtros = [
            'idClientes' => $idClientes,
            'busqueda' => trim((string) ($validated['busqueda'] ?? '')),
            'fechaDesde' => trim((string) ($validated['fechaDesde'] ?? '')),
            'fechaHasta' => trim((string) ($validated['fechaHasta'] ?? '')),
        ];

        $filas = DeterminacionesPorClienteConsulta::listado($filtros);

        if ($filas->isEmpty()) {
            abort(404);
        }

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
