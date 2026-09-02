<?php

namespace App\Http\Controllers\Protocolos;

use App\Http\Controllers\Controller;
use App\Livewire\Protocolos\PacienteIndex;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\Precios\ListaPreciosConfig;
use App\Support\Protocolos\PacienteListadoConsulta;
use App\Support\Protocolos\PacienteListadoExporter;
use App\Support\Tesoreria\TesoreriaConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PacienteListadoExcelController extends Controller
{
    public function __invoke(Request $request, PacienteListadoExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $ctx = labCtx();
        $autogestion = $ctx->esCliente();
        if ($autogestion) {
            abort_unless((bool) $ctx->idClientes, 403);
        }

        $key = 'protocolos-pacientes-xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'vista' => ['nullable', 'in:'.PacienteIndex::VISTA_HOY.','.PacienteIndex::VISTA_HISTORIAL],
            'fechaVista' => ['nullable', 'date_format:Y-m-d'],
            'fechaDesde' => ['nullable', 'date_format:Y-m-d'],
            'fechaHasta' => ['nullable', 'date_format:Y-m-d'],
            'filtroEstado' => ['nullable', 'in:'.PacienteIndex::FILTRO_PENDIENTES.','.PacienteIndex::FILTRO_LISTOS],
            'busqueda' => ['nullable', 'string', 'max:120'],
        ]);

        $filtros = [
            'vista' => (string) ($validated['vista'] ?? PacienteIndex::VISTA_HOY),
            'fechaVista' => (string) ($validated['fechaVista'] ?? ''),
            'fechaDesde' => (string) ($validated['fechaDesde'] ?? ''),
            'fechaHasta' => (string) ($validated['fechaHasta'] ?? ''),
            'filtroEstado' => (string) ($validated['filtroEstado'] ?? ''),
            'busqueda' => trim((string) ($validated['busqueda'] ?? '')),
        ];

        $filas = PacienteListadoConsulta::query($filtros)->get();

        $opciones = [
            'autogestion' => $autogestion,
            'mostrarListaPrecios' => ! $autogestion && ListaPreciosConfig::mostrarColumnaListadoPacientes(),
            'mostrarColumnaPagado' => ! $autogestion
                && TesoreriaConfig::columnaPagadoHabilitada()
                && Schema::hasColumn('pacientes', 'pagado'),
            'mostrarCadete' => ! $autogestion
                && TesoreriaConfig::usaPacientes()
                && Schema::hasColumn('pacientes', 'cadete'),
            'saldosAcumulados' => [],
            'vista' => $filtros['vista'],
            'fechaVista' => $filtros['fechaVista'],
            'fechaDesde' => $filtros['fechaDesde'],
            'fechaHasta' => $filtros['fechaHasta'],
            'busqueda' => $filtros['busqueda'],
        ];

        if ($autogestion && $ctx->idClientes && ! TesoreriaConfig::usaPacientes()) {
            $opciones['saldosAcumulados'] = CuentaCorrienteConsulta::mapaSaldoAcumuladoPorProtocolo(
                (int) $ctx->idClientes
            );
        }

        $resultado = $exporter->buildXlsx($filas, $opciones);

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
