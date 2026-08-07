<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\MovimientosCajaConsulta;
use App\Support\Tesoreria\MovimientosCajaExporter;
use App\Support\Tesoreria\TesoreriaConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovimientosCajaExcelController extends Controller
{
    public function __invoke(Request $request, MovimientosCajaExporter $exporter): StreamedResponse
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);
        abort_unless(Schema::hasTable('movimientos'), 404);

        $key = 'tesoreria-movimientos-caja-xlsx:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 120);

        $validated = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:120'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $busqueda = trim((string) ($validated['busqueda'] ?? ''));
        $desde = trim((string) ($validated['desde'] ?? ''));
        $hasta = trim((string) ($validated['hasta'] ?? ''));

        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $filas = MovimientosCajaConsulta::listado(
            $busqueda,
            $desde !== '' ? $desde : null,
            $hasta !== '' ? $hasta : null,
        )->get();

        $resultado = $exporter->buildXlsx($filas, $busqueda, $desde, $hasta);

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
