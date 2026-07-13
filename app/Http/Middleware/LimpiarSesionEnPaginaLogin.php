<?php

namespace App\Http\Middleware;

use App\Support\Auth\CerrarSesionAplicacion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Al abrir login, invalida cualquier sesión previa antes de mostrar el formulario.
 *
 * Sustituye al middleware `guest`, que redirigía a usuarios autenticados al dashboard
 * sin permitir un nuevo ingreso con credenciales.
 */
class LimpiarSesionEnPaginaLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CerrarSesionAplicacion::haySesionAutenticadaOLegacy($request)) {
            CerrarSesionAplicacion::ejecutar($request, invalidarSesion: false);
        }

        return $next($request);
    }
}
