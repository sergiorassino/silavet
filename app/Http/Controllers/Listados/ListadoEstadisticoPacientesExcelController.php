<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Listados\ListadoEstadisticoPacientesConsulta;
use App\Support\Listados\ListadoEstadisticoPacientesExporter;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListadoEstadisticoPacientesExcelController extends Controller
{
    public function __invoke(Request $request, ListadoEstadisticoPacientesExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'lep:xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'idClientes' => ['nullable', 'integer'],
            'paciente' => ['nullable', 'string', 'max:120'],
            'idEspecies' => ['nullable', 'integer'],
            'idRazas' => ['nullable', 'integer'],
            'fechaDesde' => ['nullable', 'date'],
            'fechaHasta' => ['nullable', 'date'],
            'agruparPorCliente' => ['nullable', 'boolean'],
        ]);

        $ctx = labCtx();
        $idClientes = isset($validated['idClientes']) ? (int) $validated['idClientes'] : null;
        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $filtros = [
            'idClientes' => $idClientes,
            'paciente' => trim((string) ($validated['paciente'] ?? '')),
            'idEspecies' => isset($validated['idEspecies']) ? (int) $validated['idEspecies'] : null,
            'idRazas' => isset($validated['idRazas']) ? (int) $validated['idRazas'] : null,
            'fechaDesde' => trim((string) ($validated['fechaDesde'] ?? '')),
            'fechaHasta' => trim((string) ($validated['fechaHasta'] ?? '')),
            'agruparPorCliente' => (bool) ($validated['agruparPorCliente'] ?? false),
        ];

        $filas = ListadoEstadisticoPacientesConsulta::listado($filtros);

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
