<?php

namespace App\Support\Autoanalizadores;

/**
 * Lee aparatos activos y perfiles desde config('tenant.autoanalizadores').
 */
final class AutoanalizadorConfig
{
    public static function diasRetencion(): int
    {
        return max(1, (int) config('tenant.autoanalizadores.dias_retencion', 7));
    }

    /**
     * Ruta absoluta de la carpeta de CSV. Se crea si no existe.
     */
    public static function carpeta(): string
    {
        $configurada = trim((string) config('tenant.autoanalizadores.carpeta', ''));
        if ($configurada !== '') {
            $ruta = $configurada;
        } else {
            $ruta = storage_path('app/AUTOANALIZADORES');
        }

        if (! is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }

        return $ruta;
    }

    /**
     * Aparatos activos del tenant con driver registrado.
     *
     * @return list<array{clave: string, etiqueta: string, overrides: array<string, array<string, mixed>>}>
     */
    public static function aparatosActivos(): array
    {
        $raw = config('tenant.autoanalizadores.aparatos', []);
        if (! is_array($raw)) {
            return [];
        }

        $lista = [];
        foreach ($raw as $clave => $cfg) {
            if (! is_string($clave) || ! is_array($cfg)) {
                continue;
            }
            if (! ($cfg['activo'] ?? false)) {
                continue;
            }
            if (! AutoanalizadorDriverRegistry::tiene($clave)) {
                continue;
            }

            $overrides = $cfg['overrides'] ?? [];
            if (! is_array($overrides)) {
                $overrides = [];
            }

            $lista[] = [
                'clave' => $clave,
                'etiqueta' => (string) ($cfg['etiqueta'] ?? $clave),
                'overrides' => $overrides,
            ];
        }

        return $lista;
    }

    public static function hayAparatosActivos(): bool
    {
        return self::aparatosActivos() !== [];
    }

    /**
     * @return array{clave: string, etiqueta: string, overrides: array<string, array<string, mixed>>}|null
     */
    public static function aparato(string $clave): ?array
    {
        foreach (self::aparatosActivos() as $aparato) {
            if ($aparato['clave'] === $clave) {
                return $aparato;
            }
        }

        return null;
    }
}
