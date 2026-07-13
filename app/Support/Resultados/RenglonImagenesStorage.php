<?php

namespace App\Support\Resultados;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Almacenamiento de imágenes por renglón (legacy: _lib/file/doc/REPOSITORIO/).
 * En Laravel se guarda en public/REPOSITORIO/ y en BD solo el nombre de archivo.
 */
final class RenglonImagenesStorage
{
    /** @var list<string> */
    public const EXTENSIONES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public const MAX_KB = 5120;

    public static function directorioRelativo(): string
    {
        return 'REPOSITORIO';
    }

    public static function directorioAbsoluto(): string
    {
        return public_path(self::directorioRelativo());
    }

    public static function rutaAbsoluta(string $nombreImagen): string
    {
        return self::directorioAbsoluto().DIRECTORY_SEPARATOR.$nombreImagen;
    }

    public static function urlPublica(string $nombreImagen): ?string
    {
        $nombre = self::nombreSeguro($nombreImagen);
        if ($nombre === null || ! is_file(self::rutaAbsoluta($nombre))) {
            return null;
        }

        return asset(self::directorioRelativo().'/'.$nombre);
    }

    public static function guardar(UploadedFile $archivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: '');
        if (! in_array($extension, self::EXTENSIONES, true)) {
            throw ValidationException::withMessages([
                'archivos' => 'Tipo de archivo no permitido: '.$archivo->getClientOriginalName(),
            ]);
        }

        File::ensureDirectoryExists(self::directorioAbsoluto());

        $nombreNuevo = date('Ymd_His').'_'.random_int(100, 999).'.'.$extension;
        $destino = self::rutaAbsoluta($nombreNuevo);
        $origen = $archivo->getRealPath();

        // Livewire TemporaryUploadedFile en Windows falla con move(); copiar es fiable.
        if ($origen === false || ! is_file($origen)) {
            throw ValidationException::withMessages([
                'archivos' => 'No se pudo leer el archivo temporal subido.',
            ]);
        }

        if (! File::copy($origen, $destino) || ! is_file($destino)) {
            throw ValidationException::withMessages([
                'archivos' => 'No se pudo guardar la imagen en REPOSITORIO.',
            ]);
        }

        return $nombreNuevo;
    }

    public static function eliminarArchivo(string $nombreImagen): void
    {
        $nombre = self::nombreSeguro($nombreImagen);
        if ($nombre === null) {
            return;
        }

        $ruta = self::rutaAbsoluta($nombre);
        if (is_file($ruta)) {
            File::delete($ruta);
        }
    }

    public static function nombreSeguro(string $nombreImagen): ?string
    {
        $nombre = basename(str_replace(["\0", '\\', '/'], '', $nombreImagen));
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $nombre)) {
            return null;
        }

        return $nombre;
    }
}
