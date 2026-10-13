<?php

namespace App\Http\Middleware;

use App\Support\Tutor\TutorContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTutorContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! TutorContext::fromSession()->isValid()) {
            return redirect()
                ->route('login.tutor')
                ->with('error', 'Debe iniciar sesión para continuar.');
        }

        return $next($request);
    }
}
