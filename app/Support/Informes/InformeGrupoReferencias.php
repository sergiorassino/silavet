<?php

namespace App\Support\Informes;

/**
 * Encabezado «VALORES DE REFERENCIA» por grupo en el PDF del informe.
 */
final class InformeGrupoReferencias
{
    /** Nombre de grupo que nunca imprime el encabezado, aunque el flag sea 1. */
    public const GRUPO_SIN_ENCABEZADO = 'OBSERVACIONES';

    public static function mostrarEncabezado(string $nombreGrupo, int $mostrarReferencias): bool
    {
        if (mb_strtoupper(trim($nombreGrupo)) === self::GRUPO_SIN_ENCABEZADO) {
            return false;
        }

        return $mostrarReferencias === 1;
    }
}
