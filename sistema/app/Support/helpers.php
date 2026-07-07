<?php

use App\Support\LabContext;
use App\Support\PermisosIaCatalog;

if (! function_exists('vl_route_url')) {
    /**
     * URL absoluta con el prefijo de APP_URL (subcarpeta en producción).
     */
    function vl_route_url(string $name, mixed $parameters = []): string
    {
        return rtrim((string) config('app.url'), '/').route($name, $parameters, false);
    }
}

if (! function_exists('tenantSlug')) {
    function tenantSlug(): string
    {
        $slug = trim((string) config('tenant.slug', ''));
        if ($slug === '') {
            $slug = 'default';
        }

        $slug = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $slug) ?? 'default';
        $slug = trim((string) $slug, '-');

        return $slug !== '' ? strtolower($slug) : 'default';
    }
}

if (! function_exists('labCtx')) {
    function labCtx(): LabContext
    {
        return app(LabContext::class);
    }
}

if (! function_exists('tienePermiso')) {
    function tienePermiso(int $orden): bool
    {
        return PermisosIaCatalog::usuarioTienePermiso($orden);
    }
}

if (! function_exists('labLogoUrl')) {
    function labLogoUrl(): ?string
    {
        return \App\Support\Entorno\LabInstitucional::logoUrl();
    }
}

if (! function_exists('labListaPreciosUrl')) {
    function labListaPreciosUrl(): ?string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('entorno')) {
            $entorno = \App\Models\Entorno::query()->find(1);
            if ($entorno !== null) {
                return \App\Support\Entorno\EntornoArchivos::urlPublica($entorno->listaPreciosPdf ?? null);
            }
        }

        return null;
    }
}
