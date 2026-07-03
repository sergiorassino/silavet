<?php

namespace App\Http\Middleware;

use App\Support\UsuarioMenuPortal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuPortal
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $ctx = labCtx();
        $idRoles = (int) ($ctx->idRoles ?? 0);
        $idClientes = (int) ($ctx->idClientes ?? 0);

        $allowed = match ($portal) {
            'laboratorio' => ! UsuarioMenuPortal::esAdministracion($idRoles) && ! UsuarioMenuPortal::esCliente($idClientes),
            'administracion' => UsuarioMenuPortal::esAdministracion($idRoles),
            'cliente' => UsuarioMenuPortal::esCliente($idClientes),
            default => false,
        };

        if (! $allowed) {
            return redirect()->route(UsuarioMenuPortal::rutaInicio($idRoles, $idClientes));
        }

        return $next($request);
    }
}
