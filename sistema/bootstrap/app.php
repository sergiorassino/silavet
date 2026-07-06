<?php

use App\Http\Middleware\EnsureLabContext;
use App\Http\Middleware\EnsureMenuPortal;
use App\Http\Middleware\ForceHttpsBehindProxy;
use App\Http\Middleware\RegenerarSesionPostLogin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->prependToGroup('web', ForceHttpsBehindProxy::class);
        $middleware->appendToGroup('web', RegenerarSesionPostLogin::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            if (! $request->expectsJson() && $request->hasSession()) {
                $request->session()->flash(
                    'error',
                    'Debe iniciar sesión para continuar.',
                );
            }

            $path = trim($request->path(), '/');

            if ($path === 'cliente' || str_starts_with($path, 'cliente/')) {
                return vl_route_url('cliente.login');
            }

            return vl_route_url('login');
        });

        $middleware->alias([
            'lab.context' => EnsureLabContext::class,
            'permiso' => \App\Http\Middleware\CheckPermiso::class,
            'menu.portal' => EnsureMenuPortal::class,
            'login.limpiar-sesion' => \App\Http\Middleware\LimpiarSesionEnPaginaLogin::class,
            'no-store' => \App\Http\Middleware\NoStoreResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
