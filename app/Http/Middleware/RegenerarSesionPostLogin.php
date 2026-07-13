<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegenerarSesionPostLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->session()->pull('auth.pending_session_regenerate')) {
            $request->session()->regenerate();
        }

        return $response;
    }
}
