<?php

namespace App\Support\Resultados;

class ResultadosEstadosCatalog
{
    public const EN_PROC = 'En Proc.';

    public const PARCIAL = 'Parcial';

    public const FINAL = 'Final';

    public const FINAL_ENV = 'Final/Env';

    /** @return list<string> */
    public static function valores(): array
    {
        return [self::EN_PROC, self::PARCIAL, self::FINAL, self::FINAL_ENV];
    }

    public static function esValido(string $estado): bool
    {
        return in_array($estado, self::valores(), true);
    }

    public static function normalizar(?string $estado): string
    {
        $estado = trim((string) $estado);

        return self::esValido($estado) ? $estado : self::EN_PROC;
    }

    /** Siguiente estado en el bucle: En Proc. → Parcial → Final → Final/Env → En Proc. */
    public static function siguiente(?string $estado): string
    {
        $actual = self::normalizar($estado);
        $valores = self::valores();
        $idx = array_search($actual, $valores, true);
        $siguiente = $idx === false ? 0 : ($idx + 1) % count($valores);

        return $valores[$siguiente];
    }
}
