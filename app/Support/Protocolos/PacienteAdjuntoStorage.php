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
        return self::rutaSiExiste($nombreArchivo) !== null;
    }

    /**
     * Ruta absoluta si el PDF está dentro de public/adjuntos/.
     * Acepta el nombre solo o una ruta relativa tipo adjuntos/archivo.pdf (NeoLab).
     */
    public static function rutaSiExiste(string $nombreArchivo): ?string
    {
        $nombre = self::nombreSeguro($nombreArchivo);
        if ($nombre === null) {
            return null;
        }

        $dirReal = realpath(self::directorioAbsoluto());
        if ($dirReal === false) {
            return null;
        }

        $candidato = self::rutaAbsoluta($nombre);
        if (! is_file($candidato)) {
            return null;
        }

        $fileReal = realpath($candidato);
        if ($fileReal === false || ! self::estaDentroDeDirectorio($dirReal, $fileReal)) {
            return null;
        }

        return $fileReal;
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
        $ruta = self::rutaSiExiste($nombreArchivo);
        if ($ruta !== null) {
            File::delete($ruta);
        }
    }

    /**
     * Nombre de archivo (basename) apto para buscar en public/adjuntos/.
     * Conserva espacios y acentos de nombres legacy; rechaza traversal.
     */
    public static function nombreSeguro(string $nombreArchivo): ?string
    {
        $normalizado = str_replace('\\', '/', str_replace("\0", '', $nombreArchivo));
        $nombre = trim(basename($normalizado));
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            return null;
        }

        if (str_contains($nombre, '/') || str_contains($nombre, '\\')) {
            return null;
        }

        if (! str_ends_with(strtolower($nombre), '.'.self::EXTENSION)) {
            return null;
        }

        return $nombre;
    }

    private static function estaDentroDeDirectorio(string $directorioReal, string $archivoReal): bool
    {
        $dir = rtrim(str_replace('\\', '/', $directorioReal), '/').'/';
        $file = str_replace('\\', '/', $archivoReal);

        if (str_starts_with($file, $dir)) {
            return true;
        }

        return str_starts_with(strtolower($file), strtolower($dir));
    }
}
