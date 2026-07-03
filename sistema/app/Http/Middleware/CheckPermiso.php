<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermiso
{
    public function handle(Request $request, Closure $next, int $orden): Response
    {
        abort_unless(tienePermiso($orden), 403);

        return $next($request);
    }
}
