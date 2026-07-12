<?php

namespace App\Support\Protocolos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Almacenamiento de PDF adjunto por paciente (un archivo por protocolo).
 * Se guarda en public/adjuntos/ y en BD (pacientes.adjunto) solo el nombre.
 */
final class PacienteAdjuntoStorage
{
    public const EXTENSION = 'pdf';

    /** Tamaño máximo en KB (10 MB). */
    public const MAX_KB = 10240;

    public static function directorioRelativo(): string
    {
        return 'adjuntos';
    }

    public static function directorioAbsoluto(): string
    {
        return public_path(self::directorioRelativo());
    }

    public static function rutaAbsoluta(string $nombreArchivo): string
    {
        return self::directorioAbsoluto().DIRECTORY_SEPARATOR.$nombreArchivo;
    }

    public static function existe(string $nombreArchivo): bool
    {
        $nombre = self::nombreSeguro($nombreArchivo);

        return $nombre !== null && is_file(self::rutaAbsoluta($nombre));
    }

    public static function guardar(UploadedFile $archivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: '');
        if ($extension !== self::EXTENSION) {
            throw ValidationException::withMessages([
                'adjuntoArchivo' => 'Solo se permiten archivos PDF.',
            ]);
        }

        File::ensureDirectoryExists(self::directorioAbsoluto());

        $nombreNuevo = date('Ymd_His').'_'.random_int(100, 999).'.'.self::EXTENSION;
        $destino = self::rutaAbsoluta($nombreNuevo);
        $origen = $archivo->getRealPath();

        if ($origen === false || ! is_file($origen)) {
            throw ValidationException::withMessages([
                'adjuntoArchivo' => 'No se pudo leer el archivo temporal subido.',
            ]);
        }

        if (! File::copy($origen, $destino) || ! is_file($destino)) {
            throw ValidationException::withMessages([
                'adjuntoArchivo' => 'No se pudo guardar el PDF en adjuntos.',
            ]);
        }

        return $nombreNuevo;
    }

    public static function eliminarArchivo(string $nombreArchivo): void
    {
        $nombre = self::nombreSeguro($nombreArchivo);
        if ($nombre === null) {
            return;
        }

        $ruta = self::rutaAbsoluta($nombre);
        if (is_file($ruta)) {
            File::delete($ruta);
        }
    }

    public static function nombreSeguro(string $nombreArchivo): ?string
    {
        $nombre = basename(str_replace(["\0", '\\', '/'], '', $nombreArchivo));
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $nombre)) {
            return null;
        }

        if (! str_ends_with(strtolower($nombre), '.'.self::EXTENSION)) {
            return null;
        }

        return $nombre;
    }
}
