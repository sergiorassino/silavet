<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Entorno;
use App\Support\Entorno\EntornoArchivos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListaPreciosPdfController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless(labCtx()->esCliente(), 403);

        $uid = (int) (auth()->id() ?? 0);
        $key = 'cliente-lista-precios-pdf:'.$uid;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Demasiadas solicitudes. Intente nuevamente en breve.');
        }
        RateLimiter::hit($key, 60);

        abort_unless(Schema::hasTable('entorno'), 404);

        $entorno = Entorno::query()->find(1);
        $rutaRelativa = EntornoArchivos::normalizarRutaLegacy($entorno?->listaPreciosPdf ?? null);
        $path = EntornoArchivos::rutaAbsoluta($rutaRelativa);
        if ($path === null) {
            abort(404, 'No hay lista de precios disponible.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lista-precios.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
