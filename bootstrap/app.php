<?php

use App\Http\Middleware\CheckPermiso;
use App\Http\Middleware\EnsureLabContext;
use App\Http\Middleware\EnsureMenuPortal;
use App\Http\Middleware\EnsureTesoreriaModuloVisible;
use App\Http\Middleware\ForceHttpsBehindProxy;
use App\Http\Middleware\LimpiarSesionEnPaginaLogin;
use App\Http\Middleware\NoStoreResponse;
use App\Http\Middleware\OlvidarFiltrosListadoPacientesFueraDelModulo;
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
        $middleware->appendToGroup('web', OlvidarFiltrosListadoPacientesFueraDelModulo::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            if (! $request->expectsJson() && $request->hasSession()) {
                $request->session()->flash(
                    'error',
                    'Debe iniciar sesión para continuar.',
                );
            }

            $path = trim($request->path(), '/');

            // Login único para personal y autogestión de cliente.
            return vl_route_url('login');
        });

        $middleware->alias([
            'lab.context' => EnsureLabContext::class,
            'permiso' => CheckPermiso::class,
            'menu.portal' => EnsureMenuPortal::class,
            'login.limpiar-sesion' => LimpiarSesionEnPaginaLogin::class,
            'no-store' => NoStoreResponse::class,
            'tesoreria.modulo' => EnsureTesoreriaModuloVisible::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
