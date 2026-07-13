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

        if (config('tenant.acceso.temporal_todos_modulos', false)
            && $portal !== 'cliente'
            && ! UsuarioMenuPortal::esCliente($idRoles, $idClientes)) {
            return $next($request);
        }

        $allowed = match ($portal) {
            'laboratorio' => ! UsuarioMenuPortal::esAdministracion($idRoles) && ! UsuarioMenuPortal::esCliente($idRoles, $idClientes),
            'administracion' => UsuarioMenuPortal::esAdministracion($idRoles),
            'cliente' => UsuarioMenuPortal::esCliente($idRoles, $idClientes),
            'staff' => ! UsuarioMenuPortal::esCliente($idRoles, $idClientes),
            default => false,
        };

        if (! $allowed) {
            return redirect()->route(UsuarioMenuPortal::rutaInicio($idRoles ?: null, $idClientes ?: null));
        }

        return $next($request);
    }
}
