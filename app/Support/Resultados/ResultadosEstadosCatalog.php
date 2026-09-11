<?php

namespace App\Support\Resultados;

class ResultadosEstadosCatalog
{
    public const EN_PROC = 'En Proc.';

    public const PARCIAL = 'Parcial';

    public const FINAL = 'Final';

    public const FINAL_ENV = 'Final/Env';

    /** Slugs estables para query string / Livewire (evitan espacios y `/`). */
    public const SLUG_EN_PROC = 'en-proc';

    public const SLUG_PARCIAL = 'parcial';

    public const SLUG_FINAL = 'final';

    public const SLUG_FINAL_ENV = 'final-env';

    private const SLUGS = [
        self::EN_PROC => self::SLUG_EN_PROC,
        self::PARCIAL => self::SLUG_PARCIAL,
        self::FINAL => self::SLUG_FINAL,
        self::FINAL_ENV => self::SLUG_FINAL_ENV,
    ];

    private const COLOR_DASHBOARD_EN_PROC = '#94a3b8';

    private const COLOR_DASHBOARD_PARCIAL = '#f59e0b';

    private const COLOR_DASHBOARD_FINAL = '#ef4444';

    private const COLOR_DASHBOARD_FINAL_ENV = '#66FFCC';

    public static function usaFinalEnv(): bool
    {
        return self::cantidadEstadosFlujo() === 4;
    }

    public static function cantidadEstadosFlujo(): int
    {
        $cantidad = (int) config('tenant.protocolos.estados_flujo', 4);

        return in_array($cantidad, [3, 4], true) ? $cantidad : 4;
    }

    /** @return list<string> */
    public static function valores(): array
    {
        $estados = [self::EN_PROC, self::PARCIAL, self::FINAL];

        if (self::usaFinalEnv()) {
            $estados[] = self::FINAL_ENV;
        }

        return $estados;
    }

    /**
     * Estados que cierran el protocolo (derivaciones, pendientes de finalización).
     *
     * @return list<string>
     */
    public static function estadosFinalizados(): array
    {
        if (self::usaFinalEnv()) {
            return [self::FINAL, self::FINAL_ENV];
        }

        return [self::FINAL];
    }

    public static function esValido(string $estado): bool
    {
        return in_array($estado, self::valores(), true);
    }

    /**
     * Opciones del filtro de listado según `estados_flujo` del tenant.
     *
     * @return list<array{slug: string, etiqueta: string}>
     */
    public static function opcionesFiltroListado(): array
    {
        $opciones = [];
        foreach (self::valores() as $estado) {
            $opciones[] = [
                'slug' => self::SLUGS[$estado],
                'etiqueta' => $estado,
            ];
        }

        return $opciones;
    }

    /** @return list<string> */
    public static function slugsFiltroListado(): array
    {
        return array_column(self::opcionesFiltroListado(), 'slug');
    }

    public static function slugDeEstado(?string $estado): ?string
    {
        $estado = trim((string) $estado);

        return self::SLUGS[$estado] ?? null;
    }

    public static function estadoDesdeFiltroSlug(string $slug): ?string
    {
        $slug = trim($slug);
        foreach (self::valores() as $estado) {
            if (self::SLUGS[$estado] === $slug) {
                return $estado;
            }
        }

        return null;
    }

    public static function normalizar(?string $estado): string
    {
        $estado = trim((string) $estado);

        if (self::esValido($estado)) {
            return $estado;
        }

        if ($estado === self::FINAL_ENV && ! self::usaFinalEnv()) {
            return self::FINAL;
        }

        return self::EN_PROC;
    }

    /** Siguiente estado en el bucle según la cantidad configurada por tenant. */
    public static function siguiente(?string $estado): string
    {
        $actual = self::normalizar($estado);
        $valores = self::valores();
        $idx = array_search($actual, $valores, true);
        $siguiente = $idx === false ? 0 : ($idx + 1) % count($valores);

        return $valores[$siguiente];
    }

    public static function colorDashboard(?string $estado): string
    {
        $estado = self::normalizar($estado);

        return match ($estado) {
            self::PARCIAL => self::COLOR_DASHBOARD_PARCIAL,
            self::FINAL => self::usaFinalEnv()
                ? self::COLOR_DASHBOARD_FINAL
                : self::COLOR_DASHBOARD_FINAL_ENV,
            self::FINAL_ENV => self::COLOR_DASHBOARD_FINAL_ENV,
            default => self::COLOR_DASHBOARD_EN_PROC,
        };
    }

    public static function claseCssFila(?string $estado): string
    {
        $estado = self::normalizar($estado);

        return match ($estado) {
            self::PARCIAL => 'vl-pacientes-row--parcial',
            self::FINAL => self::usaFinalEnv()
                ? 'vl-pacientes-row--final'
                : 'vl-pacientes-row--final-env',
            self::FINAL_ENV => 'vl-pacientes-row--final-env',
            default => 'vl-pacientes-row--en-proc',
        };
    }
}
