<?php

namespace App\Support\Protocolos;

use App\Livewire\Protocolos\PacienteIndex;
use App\Support\LabContext;
use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/**
 * Conserva los filtros del listado de pacientes al ir a editar / determinaciones /
 * resultados (y al volver), y mientras no se cierre el módulo.
 *
 * Query params alineados con PacienteIndex: vista, filtroEstado, fechaVista, page.
 * Al volver también puede incluirse `foco` (idPacientes) para posicionar la fila.
 *
 * Persistencia: sesión PHP hasta que el usuario cambie el filtro o navegue a otra
 * sección (fuera de protocolos / pacientes del portal).
 *
 * @phpstan-type Filtros array{vista?: string, filtroEstado?: string, fechaVista?: string, page?: int}
 */
final class PacienteListadoFiltros
{
    /**
     * Query string + sesión (la URL gana por clave). Así Volver y el menú
     * “Gestión de Pacientes” restauran día / historial.
     *
     * @return Filtros
     */
    public static function desdeRequest(): array
    {
        return self::combinar(self::desdeSesion(), self::desdeQueryString());
    }

    /**
     * Solo claves presentes en la query (incluye `vista=hoy` si vino explícito).
     *
     * @return Filtros
     */
    public static function desdeQueryString(?Request $request = null): array
    {
        $request ??= request();
        $raw = [];

        foreach (['vista', 'filtroEstado', 'fechaVista', 'page'] as $clave) {
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

        session()->forget([
            "lab.paciente_index.filtros.{$uid}.staff",
            "lab.paciente_index.filtros.{$uid}.cliente",
        ]);
    }

    /**
     * Sesión gana los huecos; las claves de `$query` pisan.
     *
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

        $vista = trim((string) ($filtros['vista'] ?? ''));
        if (in_array($vista, [PacienteIndex::VISTA_HOY, PacienteIndex::VISTA_HISTORIAL], true)) {
            if (! $omitirDefaults || $vista !== PacienteIndex::VISTA_HOY) {
                $out['vista'] = $vista;
            }
        }

        $filtroEstado = trim((string) ($filtros['filtroEstado'] ?? ''));
        if (in_array($filtroEstado, [PacienteIndex::FILTRO_PENDIENTES, PacienteIndex::FILTRO_LISTOS], true)) {
            $out['filtroEstado'] = $filtroEstado;
        }

        $fechaVista = trim((string) ($filtros['fechaVista'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVista) === 1) {
            if (! $omitirDefaults || $fechaVista !== now()->toDateString()) {
                $out['fechaVista'] = $fechaVista;
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
    public static function urlIndex(array $filtros = [], ?int $focoIdPaciente = null): string
    {
        $params = $filtros !== [] ? self::sanitizar($filtros) : self::sanitizar(self::desdeRequest());

        if ($focoIdPaciente !== null && $focoIdPaciente > 0) {
            $params['foco'] = $focoIdPaciente;
        }

        return route('protocolos.index', $params);
    }

    /**
     * @param  Filtros  $filtros
     */
    public static function urlIndexCliente(array $filtros = [], ?int $focoIdPaciente = null): string
    {
        $params = $filtros !== [] ? self::sanitizar($filtros) : self::sanitizar(self::desdeRequest());

        if ($focoIdPaciente !== null && $focoIdPaciente > 0) {
            $params['foco'] = $focoIdPaciente;
        }

        return route('cliente.pacientes', $params);
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

        if ($request->routeIs('derivaciones.*')) {
            return false;
        }

        $origen = trim((string) $request->query('origen', ''));
        if ($origen === 'derivaciones' && $request->routeIs('protocolos.edit', 'protocolos.resultados')) {
            return false;
        }

        return $request->routeIs(
            'protocolos.*',
            'cliente.pacientes',
            'cliente.pacientes.*',
            'facturacion.afip.comprobantes',
        );
    }

    public static function claveSesion(): ?string
    {
        $uid = (int) (auth()->id() ?? 0);
        if ($uid < 1) {
            return null;
        }

        $portal = 'staff';
        if (app()->bound(LabContext::class) && labCtx()->esCliente()) {
            $portal = 'cliente';
        }

        return "lab.paciente_index.filtros.{$uid}.{$portal}";
    }
}
