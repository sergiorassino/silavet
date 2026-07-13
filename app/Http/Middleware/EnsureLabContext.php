<?php

namespace App\Http\Middleware;

use App\Support\LabContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLabContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $ctx = labCtx();

        if (! $ctx->isValid()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Sesión inválida. Inicie sesión nuevamente.');
        }

        return $next($request);
    }
}
