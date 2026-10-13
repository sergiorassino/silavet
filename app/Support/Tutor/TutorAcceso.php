<?php

namespace App\Support\Tutor;

use App\Support\DniInput;
use App\Support\Resultados\ResultadosEstadosCatalog;

/**
 * Identificación del portal del tutor.
 *
 * No hay usuario en `usuarios`: el tutor entra con el DNI cargado en `pacientes.dni`.
 */
final class TutorAcceso
{
    /** Misma longitud que `pacientes.dni` (VARCHAR 8). */
    public const DNI_MAX = 8;

    public static function dni(string $value): string
    {
        return DniInput::normalize($value, self::DNI_MAX);
    }

    /**
     * Siempre Final y Final/Env, aunque el tenant use flujo de 3 estados.
     *
     * @return list<string>
     */
    public static function estadosVisibles(): array
    {
        return [
            ResultadosEstadosCatalog::FINAL,
            ResultadosEstadosCatalog::FINAL_ENV,
        ];
    }
}
