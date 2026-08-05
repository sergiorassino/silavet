<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta;
use App\Support\CuentaCorriente\CuentaCorrienteMovimientosDetalleTcpdf;
use App\Support\CuentaCorriente\LabEntornoPdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CuentaCorrienteMovimientosDetallePdfController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cc-mov:detalle-pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

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
        $filas = CuentaCorrienteMovimientosConsulta::movimientosCliente($id, $desde, $hasta);
        $resumen = CuentaCorrienteMovimientosConsulta::resumenMovimientos($filas);
        $saldoAnterior = CuentaCorrienteMovimientosConsulta::saldoAnteriorAFecha($id, $desde);

        $pdf = CuentaCorrienteMovimientosDetalleTcpdf::generar([
            'header' => LabEntornoPdf::datosHeader(),
            'cliente_nombre' => (string) $cliente->nombre,
            'periodo_texto' => CuentaCorrienteMovimientosConsulta::etiquetaPeriodo($desde, $hasta),
            'filas' => $filas->all(),
            'total_monto' => $resumen['total_monto'],
            'saldo_anterior' => $saldoAnterior,
            'fecha_desde' => $desde,
        ]);

        return CuentaCorrienteMovimientosDetalleTcpdf::respuestaHttp($pdf, 'cuenta-corriente-detalle.pdf');
    }
}
