<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\CuentaCorriente\CuentaCorrienteExporter;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CuentaCorrienteClientesExcelController extends Controller
{
    public function __invoke(Request $request, CuentaCorrienteExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cc:clientes-xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:120'],
        ]);

        $busqueda = trim((string) ($validated['busqueda'] ?? ''));
        $clientes = CuentaCorrienteConsulta::clientesListado($busqueda);

        if ($clientes->isEmpty()) {
            abort(404);
        }

        $resultado = $exporter->buildXlsxClientes($clientes, $busqueda);

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
