<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Listados\HistorialDeterminacionesConsulta;
use App\Support\Listados\HistorialDeterminacionesExporter;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistorialDeterminacionesExcelController extends Controller
{
    public function __invoke(Request $request, HistorialDeterminacionesExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'hd:xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'idClientes' => ['nullable', 'integer'],
            'paciente' => ['nullable', 'string', 'max:120'],
            'idEspecies' => ['nullable', 'integer'],
            'protocolo' => ['nullable', 'string', 'max:40'],
            'idGrupos' => ['nullable', 'integer'],
            'idsItems' => ['nullable', 'array'],
            'idsItems.*' => ['integer'],
            'valorOperador' => ['nullable', 'string', Rule::in(HistorialDeterminacionesConsulta::OPERADORES_VALOR)],
            'valor' => ['nullable', 'string', 'max:40'],
            'valorHasta' => ['nullable', 'string', 'max:40'],
            'fechaDesde' => ['nullable', 'date'],
            'fechaHasta' => ['nullable', 'date'],
        ]);

        $ctx = labCtx();
        $idClientes = isset($validated['idClientes']) ? (int) $validated['idClientes'] : null;
        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $idsItems = array_values(array_unique(array_filter(
            array_map('intval', (array) ($validated['idsItems'] ?? [])),
            static fn (int $id): bool => $id > 0,
        )));

        $filtros = [
            'idClientes' => $idClientes,
            'paciente' => trim((string) ($validated['paciente'] ?? '')),
            'idEspecies' => isset($validated['idEspecies']) ? (int) $validated['idEspecies'] : null,
            'protocolo' => trim((string) ($validated['protocolo'] ?? '')),
            'idGrupos' => isset($validated['idGrupos']) ? (int) $validated['idGrupos'] : null,
            'idsItems' => $idsItems,
            'valorOperador' => trim((string) ($validated['valorOperador'] ?? '')),
            'valor' => trim((string) ($validated['valor'] ?? '')),
            'valorHasta' => trim((string) ($validated['valorHasta'] ?? '')),
            'fechaDesde' => trim((string) ($validated['fechaDesde'] ?? '')),
            'fechaHasta' => trim((string) ($validated['fechaHasta'] ?? '')),
        ];

        $filas = HistorialDeterminacionesConsulta::listado($filtros);

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
