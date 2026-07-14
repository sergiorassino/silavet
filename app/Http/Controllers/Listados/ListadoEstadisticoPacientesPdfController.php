<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Entorno\LabInstitucional;
use App\Support\Listados\ListadoEstadisticoPacientesConsulta;
use App\Support\Listados\ListadoEstadisticoPacientesTcpdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ListadoEstadisticoPacientesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'lep:pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $filtros = $this->filtrosValidados($request);
        $filas = ListadoEstadisticoPacientesConsulta::listado($filtros);
        $resumen = ListadoEstadisticoPacientesConsulta::resumen($filas);

        $pdf = ListadoEstadisticoPacientesTcpdf::generar([
            'header' => LabInstitucional::datosParaPdf(),
            'filas' => $filas->all(),
            'periodo_texto' => ListadoEstadisticoPacientesConsulta::etiquetaPeriodo(
                $filtros['fechaDesde'] ?? '',
                $filtros['fechaHasta'] ?? '',
            ),
            'agruparPorCliente' => (bool) ($filtros['agruparPorCliente'] ?? false),
            'total_precio' => $resumen['total_precio'],
            'total_pagado' => $resumen['total_pagado'],
        ]);

        return ListadoEstadisticoPacientesTcpdf::respuestaHttp(
            $pdf,
            'listado-estadistico-pacientes.pdf',
        );
    }

    /**
     * @return array{
     *     idClientes: int|null,
     *     paciente: string,
     *     idEspecies: int|null,
     *     idRazas: int|null,
     *     fechaDesde: string,
     *     fechaHasta: string,
     *     agruparPorCliente: bool
     * }
     */
    private function filtrosValidados(Request $request): array
    {
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

        return [
            'idClientes' => $idClientes,
            'paciente' => trim((string) ($validated['paciente'] ?? '')),
            'idEspecies' => isset($validated['idEspecies']) ? (int) $validated['idEspecies'] : null,
            'idRazas' => isset($validated['idRazas']) ? (int) $validated['idRazas'] : null,
            'fechaDesde' => trim((string) ($validated['fechaDesde'] ?? '')),
            'fechaHasta' => trim((string) ($validated['fechaHasta'] ?? '')),
            'agruparPorCliente' => (bool) ($validated['agruparPorCliente'] ?? false),
        ];
    }
}
