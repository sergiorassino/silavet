<?php

namespace App\Http\Middleware;

use App\Support\Tesoreria\TesoreriaConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTesoreriaModuloVisible
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(TesoreriaConfig::mostrarModulo(), 404);

        return $next($request);
    }
}
