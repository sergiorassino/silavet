<?php

namespace App\Support\Tesoreria;

use App\Support\Facturacion\FacturacionAfipConfig;
use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/**
 * Conserva filtros del listado labvetciudad (`MovimientosCajaIndex`: Desde/Hasta,
 * página) al navegar dentro del módulo y hasta salir de Tesorería → Movimientos.
 *
 * @phpstan-type Filtros array{fechaDesde?: string, fechaHasta?: string, page?: int}
 */
final class MovimientosCajaListadoFiltros
{
    /**
     * @return Filtros
     */
    public static function desdeRequest(): array
    {
        return self::combinar(self::desdeSesion(), self::desdeQueryString());
    }

    /**
     * @return Filtros
     */
    public static function desdeQueryString(?Request $request = null): array
    {
        $request ??= request();
        $raw = [];

        foreach (['fechaDesde', 'fechaHasta', 'page'] as $clave) {
            if ($request->query->has($clave)) {
                $raw[$clave] = $request->query($clave);
            }
        }

        return self::sanitizar($raw, omitirDefaults: false);
    }

    /**
     * @return Filtros
     */
    public static function desdeSesion(): array
    {
        $clave = self::claveSesion();
        if ($clave === null) {
            return [];
        }

        $raw = session($clave, []);
        if (! is_array($raw)) {
            return [];
        }

        return self::sanitizar($raw, omitirDefaults: false);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public static function guardarSesion(array $filtros): void
    {
        $clave = self::claveSesion();
        if ($clave === null) {
            return;
        }

        session([$clave => self::sanitizar($filtros, omitirDefaults: false)]);
    }

    public static function olvidarSesion(): void
    {
        $uid = (int) (auth()->id() ?? 0);
        if ($uid < 1) {
            return;
        }

        session()->forget("lab.tesoreria_caja_movimientos.filtros.{$uid}.staff");
    }

    /**
     * @param  Filtros  $sesion
     * @param  Filtros  $query
     * @return Filtros
     */
    public static function combinar(array $sesion, array $query): array
    {
        return self::sanitizar(array_merge($sesion, $query), omitirDefaults: false);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return Filtros
     */
    public static function sanitizar(array $filtros, bool $omitirDefaults = true): array
    {
        $out = [];
        $hoy = now()->toDateString();

        foreach (['fechaDesde', 'fechaHasta'] as $campo) {
            $fecha = trim((string) ($filtros[$campo] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1) {
                if (! $omitirDefaults || $fecha !== $hoy) {
                    $out[$campo] = $fecha;
                }
            }
        }

        $page = (int) ($filtros['page'] ?? 0);
        if ($page > 1) {
            $out['page'] = $page;
        }

        return $out;
    }

    /**
     * @param  Filtros  $filtros
     */
    public static function urlIndex(array $filtros = []): string
    {
        $params = $filtros !== [] ? self::sanitizar($filtros) : self::sanitizar(self::desdeRequest());

        return route('tesoreria.movimientos.index', $params);
    }

    public static function requestPerteneceAlModulo(?Request $request = null): bool
    {
        $request ??= request();

        if ($request->headers->has('X-Livewire')) {
            return true;
        }

        $prefijoLivewire = trim(EndpointResolver::prefix(), '/');
        if ($prefijoLivewire !== '' && $request->is($prefijoLivewire.'/*')) {
            return true;
        }

        if ($request->is('livewire/*')) {
            return true;
        }

        $route = $request->route();
        if ($route === null || $route->getName() === null) {
            return true;
        }

        if ($request->routeIs('tesoreria.movimientos.index', 'tesoreria.movimientos.excel')) {
            return TesoreriaConfig::usaPacientes();
        }

        if ($request->routeIs('facturacion.afip.comprobantes') && FacturacionAfipConfig::esModoMovimientoCaja()) {
            return true;
        }

        return false;
    }

    public static function claveSesion(): ?string
    {
        $uid = (int) (auth()->id() ?? 0);
        if ($uid < 1) {
            return null;
        }

        return "lab.tesoreria_caja_movimientos.filtros.{$uid}.staff";
    }
}
