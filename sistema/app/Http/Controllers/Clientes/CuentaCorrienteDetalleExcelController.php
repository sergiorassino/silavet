<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\CuentaCorriente\CuentaCorrienteExporter;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CuentaCorrienteDetalleExcelController extends Controller
{
    public function __invoke(Request $request, int $id, CuentaCorrienteExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cc:detalle-xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = trim((string) ($validated['desde'] ?? ''));
        $hasta = trim((string) ($validated['hasta'] ?? ''));

        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            abort(422, 'La fecha hasta debe ser posterior o igual a la fecha desde.');
        }

        $cliente = Cliente::query()->findOrFail($id);
        $filas = CuentaCorrienteConsulta::protocolosCliente($id, $desde, $hasta);
        $saldoAnterior = CuentaCorrienteConsulta::saldoAnteriorAFecha($id, $desde);

        if ($filas->isEmpty() && $saldoAnterior === null) {
            abort(404);
        }

        $resultado = $exporter->buildXlsxDetalle(
            $filas,
            (string) $cliente->nombre,
            $desde,
            $hasta,
            $saldoAnterior,
        );

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
