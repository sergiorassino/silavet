<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\CuentaCorriente\LabEntornoPdf;
use App\Support\CuentaCorriente\ResumenClienteEntreFechasTcpdf;
use App\Support\PermisosIaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ResumenClienteEntreFechasPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'resumen-cliente-fechas-pdf:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'idClientes' => ['required', 'integer', 'exists:clientes,idClientes'],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ], [
            'idClientes.required' => 'Seleccione un cliente.',
            'idClientes.exists' => 'El cliente indicado no existe.',
            'desde.required' => 'Indique la fecha desde.',
            'hasta.required' => 'Indique la fecha hasta.',
            'hasta.after_or_equal' => 'La fecha hasta debe ser posterior o igual a la fecha desde.',
        ]);

        $idClientes = (int) $validated['idClientes'];
        $desde = trim((string) $validated['desde']);
        $hasta = trim((string) $validated['hasta']);

        $cliente = Cliente::query()->find($idClientes);
        if ($cliente === null) {
            throw ValidationException::withMessages([
                'idClientes' => 'El cliente indicado no existe.',
            ]);
        }

        $filas = CuentaCorrienteConsulta::protocolosCliente($idClientes, $desde, $hasta);
        $saldoActual = CuentaCorrienteConsulta::saldoClienteHoy($idClientes);

        $pdf = ResumenClienteEntreFechasTcpdf::generar([
            'header' => LabEntornoPdf::datosHeader(),
            'cliente_nombre' => (string) $cliente->nombre,
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'saldo_actual' => $saldoActual,
            'filas' => $filas->all(),
        ]);

        return ResumenClienteEntreFechasTcpdf::respuestaHttp($pdf, 'resumen-cliente-entre-fechas.pdf');
    }
}
