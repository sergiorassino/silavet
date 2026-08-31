<?php

namespace App\Http\Middleware;

use App\Support\Protocolos\DerivacionListadoFiltros;
use App\Support\Protocolos\PacienteListadoFiltros;
use App\Support\Tesoreria\MovimientoListadoFiltros;
use App\Support\Tesoreria\MovimientosCajaListadoFiltros;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Al salir de un listado con filtros en sesión (Pacientes, Derivaciones, Movimientos
 * de Tesorería), olvida esos filtros para que la próxima entrada vuelva al default.
 */
class OlvidarFiltrosListadoPacientesFueraDelModulo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        if (! PacienteListadoFiltros::requestPerteneceAlModulo($request)) {
            PacienteListadoFiltros::olvidarSesion();
        }

        if (! DerivacionListadoFiltros::requestPerteneceAlModulo($request)) {
            DerivacionListadoFiltros::olvidarSesion();
        }

        if (! MovimientoListadoFiltros::requestPerteneceAlModulo($request)) {
            MovimientoListadoFiltros::olvidarSesion();
        }

        if (! MovimientosCajaListadoFiltros::requestPerteneceAlModulo($request)) {
            MovimientosCajaListadoFiltros::olvidarSesion();
        }

        return $next($request);
    }
}
