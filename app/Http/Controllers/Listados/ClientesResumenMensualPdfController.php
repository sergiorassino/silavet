<?php

namespace App\Http\Controllers\Listados;

use App\Http\Controllers\Controller;
use App\Support\Entorno\LabInstitucional;
use App\Support\Listados\ClientesResumenMensualConsulta;
use App\Support\Listados\ClientesResumenMensualTcpdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ClientesResumenMensualPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        $key = 'crm:pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $filtros = $this->filtrosValidados($request);
        $filas = ClientesResumenMensualConsulta::listado($filtros);
        $totales = ClientesResumenMensualConsulta::acumular($filas);
        $infoCliente = ClientesResumenMensualConsulta::infoClienteFiltro($filtros);

        $pdf = ClientesResumenMensualTcpdf::generar([
            'header' => LabInstitucional::datosParaPdf(),
            'filas' => $filas->all(),
            'totales' => $totales,
            'info_cliente' => $infoCliente,
            'periodo_texto' => ClientesResumenMensualConsulta::etiquetaPeriodo(
                $filtros['fechaDesde'],
                $filtros['fechaHasta'],
            ),
            'mensaje_vacio' => ClientesResumenMensualConsulta::mensajeVacio($filtros, $infoCliente),
        ]);

        $nombre = 'clientes-resumen-mensual_'.$filtros['fechaDesde'].'_'.$filtros['fechaHasta'].'.pdf';

        return ClientesResumenMensualTcpdf::respuestaHttp($pdf, $nombre);
    }

    /**
     * @return array{idClientes: int|null, fechaDesde: string, fechaHasta: string}
     */
    private function filtrosValidados(Request $request): array
    {
        $validated = $request->validate([
            'idClientes' => ['nullable', 'integer'],
            'fechaDesde' => ['nullable', 'date'],
            'fechaHasta' => ['nullable', 'date'],
        ]);

        return ClientesResumenMensualConsulta::normalizarFiltros([
            'idClientes' => $validated['idClientes'] ?? null,
            'fechaDesde' => trim((string) ($validated['fechaDesde'] ?? '')),
            'fechaHasta' => trim((string) ($validated['fechaHasta'] ?? '')),
        ]);
    }
}
