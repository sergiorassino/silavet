<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Entorno\LabInstitucional;
use App\Support\Listados\HistorialDeterminacionesConsulta;
use App\Support\Listados\HistorialDeterminacionesTcpdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class HistorialDeterminacionesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'hd:pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $filtros = $this->filtrosValidados($request);
        $filas = HistorialDeterminacionesConsulta::listado($filtros);

        $pdf = HistorialDeterminacionesTcpdf::generar([
            'header' => LabInstitucional::datosParaPdf(),
            'filas' => $filas->all(),
            'periodo_texto' => HistorialDeterminacionesConsulta::etiquetaPeriodo(
                $filtros['fechaDesde'] ?? '',
                $filtros['fechaHasta'] ?? '',
            ),
        ]);

        return HistorialDeterminacionesTcpdf::respuestaHttp(
            $pdf,
            'historial-determinaciones.pdf',
        );
    }

    /**
     * @return array{
     *     idClientes: int|null,
     *     paciente: string,
     *     idEspecies: int|null,
     *     protocolo: string,
     *     idGrupos: int|null,
     *     idsItems: list<int>,
     *     valorOperador: string,
     *     valor: string,
     *     valorHasta: string,
     *     fechaDesde: string,
     *     fechaHasta: string
     * }
     */
    private function filtrosValidados(Request $request): array
    {
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

        return [
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
    }
}
