<?php

namespace App\Support\Entorno;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

final class EntornoArchivos
{
    public static function directorioLogo(): string
    {
        return 'entorno/logos/'.tenantSlug();
    }

    public static function directorioFirmas(): string
    {
        return 'entorno/firmas/'.tenantSlug();
    }

    public static function directorioListaPrecios(): string
    {
        return 'entorno/lista-precios/'.tenantSlug();
    }

    /**
     * Guarda un PDF bajo public/ y devuelve la ruta relativa a public/.
     */
    public static function guardarPdf(UploadedFile $archivo, string $directorio, string $nombreBase): string
    {
        return self::guardarArchivo($archivo, $directorio, $nombreBase.'.pdf');
    }

    /**
     * Guarda una imagen bajo public/ y devuelve la ruta relativa a public/.
     */
    public static function guardarImagen(UploadedFile $archivo, string $directorio, string $nombreBase): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: 'png');
        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $extension = 'png';
        }

        return self::guardarArchivo($archivo, $directorio, $nombreBase.'.'.$extension);
    }

    private static function guardarArchivo(UploadedFile $archivo, string $directorio, string $nombreArchivo): string
    {
        $directorio = trim($directorio, '/');
        $destinoDir = public_path($directorio);
        $destino = $destinoDir.DIRECTORY_SEPARATOR.$nombreArchivo;

        File::ensureDirectoryExists($destinoDir);

        foreach (File::files($destinoDir) as $existente) {
            if ($existente->getFilenameWithoutExtension() === pathinfo($nombreArchivo, PATHINFO_FILENAME)) {
                File::delete($existente->getPathname());
            }
        }

        $origen = $archivo->getRealPath();
        // Livewire TemporaryUploadedFile ya no es un upload HTTP: move()/move_uploaded_file() falla.
        if ($origen === false || ! is_file($origen)) {
            throw new \RuntimeException('No se pudo leer el archivo temporal subido.');
        }

        if (! File::copy($origen, $destino) || ! is_file($destino)) {
            throw new \RuntimeException(
                'No se pudo guardar el archivo en '.$directorio.'. Verifique permisos de escritura en public/entorno.'
            );
        }

        @chmod($destino, 0644);

        return $directorio.'/'.$nombreArchivo;
    }

    /**
     * Convierte rutas legacy storage/... a public/entorno/... copiando el archivo si hace falta.
     */
    public static function normalizarRutaLegacy(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);
        if ($ruta === '') {
            return null;
        }

        if (! str_starts_with($ruta, 'storage/')) {
            return $ruta;
        }

        $relativa = substr($ruta, strlen('storage/'));
        $origen = storage_path('app/public/'.$relativa);
        if (! is_file($origen)) {
            return $ruta;
        }

        $destino = public_path($relativa);
        File::ensureDirectoryExists(dirname($destino));

        if (! is_file($destino)) {
            File::copy($origen, $destino);
        }

        return $relativa;
    }

    public static function rutaAbsoluta(?string $rutaRelativa): ?string
    {
        $ruta = trim((string) $rutaRelativa);
        if ($ruta === '') {
            return null;
        }

        $directa = public_path($ruta);
        if (is_file($directa)) {
            return $directa;
        }

        // Compatibilidad con rutas legacy storage/... (storage/app/public vía symlink o disco)
        if (str_starts_with($ruta, 'storage/')) {
            $enStorage = storage_path('app/public/'.substr($ruta, strlen('storage/')));
            if (is_file($enStorage)) {
                return $enStorage;
            }
        }

        return null;
    }

    public static function urlPublica(?string $rutaRelativa, bool $cacheBust = false): ?string
    {
        $ruta = trim((string) $rutaRelativa);
        if ($ruta === '') {
            return null;
        }

        $absoluta = self::rutaAbsoluta($ruta);
        if ($absoluta === null) {
            return null;
        }

        $url = asset($ruta);

        if ($cacheBust) {
            $url .= '?v='.filemtime($absoluta);
        }

        return $url;
    }
}
