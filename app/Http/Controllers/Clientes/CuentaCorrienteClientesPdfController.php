<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Support\CuentaCorriente\CuentaCorrienteClientesTcpdf;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\CuentaCorriente\LabEntornoPdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CuentaCorrienteClientesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cc:clientes-pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:120'],
        ]);

        $busqueda = trim((string) ($validated['busqueda'] ?? ''));
        $clientes = CuentaCorrienteConsulta::clientesListado($busqueda);
        $saldoTotal = round($clientes->sum(fn ($cliente) => (float) $cliente->saldo_total), 2);

        $filas = $clientes->map(function ($cliente) {
            $telefono = trim((string) ($cliente->telefono1 ?? ''));
            if ($telefono === '' && ! empty($cliente->telefono2)) {
                $telefono = trim((string) $cliente->telefono2);
            }

            return (object) [
                'nombre' => (string) ($cliente->nombre ?? ''),
                'direccion' => (string) ($cliente->direccion ?? ''),
                'telefono' => $telefono,
                'saldo_total' => (float) ($cliente->saldo_total ?? 0),
            ];
        })->all();

        $pdf = CuentaCorrienteClientesTcpdf::generar([
            'header' => LabEntornoPdf::datosHeader(),
            'filas' => $filas,
            'saldo_total' => $saldoTotal,
        ]);

        return CuentaCorrienteClientesTcpdf::respuestaHttp($pdf, 'cuenta-corriente-clientes.pdf');
    }
}
