<?php

namespace App\Support\Auth;

use App\Support\LabContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cierra por completo la sesión web (staff y clientes).
 *
 * Usado en logout explícito y al abrir una pantalla de login, para evitar
 * que una cookie de sesión previa permita entrar sin credenciales en equipos compartidos.
 */
final class CerrarSesionAplicacion
{
    /**
     * ¿Hay datos de autenticación o contexto que deban borrarse al abrir login?
     *
     * Si no hay nada, evitar flush/regenerateToken en cada GET: reduce carreras de CSRF
     * (doble carga, autocompletar + Livewire) que provocan 419 en bucle.
     */
    public static function teniaAutenticacionActiva(?Request $request = null): bool
    {
        $request ??= request();

        return Auth::check() || Auth::guard('cliente')->check();
    }

    public static function haySesionAutenticadaOLegacy(?Request $request = null): bool
    {
        $request ??= request();

        if (self::teniaAutenticacionActiva($request)) {
            return true;
        }

        foreach (array_keys($request->session()->all()) as $key) {
            if (str_starts_with($key, 'login_') || str_starts_with($key, 'lab.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  bool  $invalidarSesion  Si es false, conserva el id de sesión (cookie) y solo vacía datos + CSRF.
     *                                  Usar false al abrir login: evita 419 si el navegador envía el POST
     *                                  antes de aplicar la cookie de sesión nueva tras invalidate().
     */
    public static function ejecutar(?Request $request = null, bool $invalidarSesion = true): void
    {
        $request ??= request();

        LabContext::clear();

        Auth::guard('cliente')->logout();
        Auth::logout();

        if ($invalidarSesion) {
            $request->session()->invalidate();
        } else {
            $request->session()->flush();
        }

        $request->session()->regenerateToken();
    }
}
