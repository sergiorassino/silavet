<?php

namespace App\Support\CuentaCorriente;

use App\Support\Entorno\LabInstitucional;

/**
 * @deprecated Use LabInstitucional::datosParaPdf()
 */
final class LabEntornoPdf
{
    /**
     * @return array{nombre: string, direccion: string, telefono: string, logo_file: ?string}
     */
    public static function datosHeader(): array
    {
        return LabInstitucional::datosParaPdf();
    }
}
