<?php

namespace App\Support\Entorno;

use App\Models\Entorno;
use Illuminate\Support\Facades\Schema;

/**
 * Identidad institucional del laboratorio (logo, contacto, color).
 * Fuente única para vistas web y PDFs.
 */
final class LabInstitucional
{
    /**
     * @return array{
     *     nombre: string,
     *     direccion: string,
     *     telefono: string,
     *     email: string,
     *     logo_url: ?string,
     *     logo_file: ?string,
     *     color_informe: string,
     *     iniciales: string,
     * }
     */
    public static function datos(): array
    {
        return once(function () {
            $nombre = trim((string) config('tenant.nombre', 'Laboratorio Veterinario'));
            $direccion = '';
            $telefono = '';
            $email = '';
            $logoUrl = null;
            $logoFile = null;
            $colorInforme = '#0EA5E9';

            if (Schema::hasTable('entorno')) {
                $entorno = Entorno::query()->find(1);
                if ($entorno !== null) {
                    $direccion = trim((string) ($entorno->direLabo ?? ''));
                    $telefono = trim((string) ($entorno->teleLabo ?? ''));
                    $email = trim((string) ($entorno->emailLabo ?? ''));
                    $rutaLogo = EntornoArchivos::normalizarRutaLegacy(
                        trim((string) ($entorno->logo ?? '')) ?: null
                    );
                    $logoUrl = EntornoArchivos::urlPublica($rutaLogo, cacheBust: true);
                    $logoFile = EntornoArchivos::rutaAbsoluta($rutaLogo);

                    $color = trim((string) ($entorno->colorInforme ?? ''));
                    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
                        $colorInforme = strtoupper($color);
                    }
                }
            }

            if ($logoFile === null) {
                $fallback = trim((string) config('tenant.institucional.logo_fallback', ''));
                if ($fallback !== '' && is_file(public_path($fallback))) {
                    $logoFile = public_path($fallback);
                    $logoUrl = asset($fallback);
                }
            }

            return [
                'nombre' => $nombre,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'email' => $email,
                'logo_url' => $logoUrl,
                'logo_file' => $logoFile,
                'color_informe' => $colorInforme,
                'iniciales' => self::iniciales($nombre),
            ];
        });
    }

    public static function logoUrl(): ?string
    {
        return self::datos()['logo_url'];
    }

    public static function logoFile(): ?string
    {
        return self::datos()['logo_file'];
    }

    /**
     * Formato legacy para controladores PDF existentes.
     *
     * @return array{nombre: string, direccion: string, telefono: string, logo_file: ?string}
     */
    public static function datosParaPdf(): array
    {
        $datos = self::datos();

        return [
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'telefono' => $datos['telefono'],
            'logo_file' => $datos['logo_file'],
        ];
    }

    private static function iniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/u', trim($nombre)) ?: [];
        $letras = '';

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if ($parte === '') {
                continue;
            }

            $letras .= mb_strtoupper(mb_substr($parte, 0, 1));
            if (mb_strlen($letras) >= 2) {
                break;
            }
        }

        return $letras !== '' ? $letras : 'LV';
    }
}
