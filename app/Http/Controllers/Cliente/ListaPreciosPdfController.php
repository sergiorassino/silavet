<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Entorno;
use App\Support\Cliente\PortalClienteConfig;
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
        abort_unless(PortalClienteConfig::mostrarListaPrecios(), 404);

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

        $marca = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $request->route('marca', ''));
        $nombre = 'lista-precios'.($marca !== '' ? '-'.$marca : '').'.pdf';

        $respuesta = response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nombre.'"',
            'Accept-Ranges' => 'none',
        ]);

        // El visor PDF de Chrome guarda el archivo por la URL y por rangos,
        // aunque la respuesta diga no-store. Sin Last-Modified no hay 304.
        $respuesta->headers->remove('Last-Modified');
        $respuesta->headers->remove('ETag');
        $respuesta->setPrivate();
        $respuesta->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $respuesta->headers->set('Pragma', 'no-cache');
        $respuesta->headers->set('Expires', '0');
        $respuesta->headers->set('Accept-Ranges', 'none');

        return $respuesta;
    }
}
