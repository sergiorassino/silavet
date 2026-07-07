<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\CuentaCorriente\CuentaCorrienteDetalleTcpdf;
use App\Support\CuentaCorriente\LabEntornoPdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CuentaCorrienteDetallePdfController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cc:detalle-pdf:'.(auth()->id() ?? 'guest');
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
        $filas = CuentaCorrienteConsulta::protocolosCliente($id, $desde, $hasta);
        $resumen = CuentaCorrienteConsulta::resumenProtocolos($filas);
        $saldoAnterior = CuentaCorrienteConsulta::saldoAnteriorAFecha($id, $desde);

        $pdf = CuentaCorrienteDetalleTcpdf::generar([
            'header' => LabEntornoPdf::datosHeader(),
            'cliente_nombre' => (string) $cliente->nombre,
            'periodo_texto' => CuentaCorrienteConsulta::etiquetaPeriodo($desde, $hasta),
            'filas' => $filas->all(),
            'cantidad' => $resumen['cantidad'],
            'total_precio' => $resumen['total_precio'],
            'total_pagado' => $resumen['total_pagado'],
            'saldo_anterior' => $saldoAnterior,
            'fecha_desde' => $desde,
        ]);

        return CuentaCorrienteDetalleTcpdf::respuestaHttp($pdf, 'cuenta-corriente-detalle.pdf');
    }
}
