<?php

namespace App\Support\Autoanalizadores;

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

/**
 * Lista, limpia (> N días) y guarda CSV en la carpeta AUTOANALIZADORES.
 * No persiste metadatos en BD.
 */
final class AutoanalizadorCarpeta
{
    public const EXTENSIONES = ['csv', 'txt', 'shd'];

    public const MAX_KB = 10240; // 10 MB

    /**
     * Purga archivos viejos y devuelve los de los últimos N días (mtime desc).
     *
     * @return list<array{nombre: string, mtime: int, bytes: int}>
     */
    public function listarRecientes(): array
    {
        $dir = AutoanalizadorConfig::carpeta();
        $limite = time() - (AutoanalizadorConfig::diasRetencion() * 86400);
        $archivos = [];

        $items = @scandir($dir);
        if ($items === false) {
            return [];
        }

        foreach ($items as $nombre) {
            if ($nombre === '.' || $nombre === '..' || $nombre === '.gitkeep') {
                continue;
            }

            $ruta = $dir.DIRECTORY_SEPARATOR.$nombre;
            if (! is_file($ruta)) {
                continue;
            }

            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (! in_array($ext, self::EXTENSIONES, true)) {
                continue;
            }

            $mtime = (int) filemtime($ruta);
            if ($mtime < $limite) {
                @unlink($ruta);

                continue;
            }

            $archivos[] = [
                'nombre' => $nombre,
                'mtime' => $mtime,
                'bytes' => (int) filesize($ruta),
            ];
        }

        usort($archivos, fn (array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        return $archivos;
    }

    /**
     * Resuelve un nombre de archivo seguro dentro de la carpeta (anti path traversal).
     */
    public function rutaSegura(string $nombreArchivo): string
    {
        $nombre = basename(str_replace(["\0", '\\', '/'], '', $nombreArchivo));
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            throw new RuntimeException('Nombre de archivo inválido.');
        }

        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (! in_array($ext, self::EXTENSIONES, true)) {
            throw new RuntimeException('Solo se permiten archivos CSV, TXT o SHD.');
        }

        $ruta = AutoanalizadorConfig::carpeta().DIRECTORY_SEPARATOR.$nombre;
        $realDir = realpath(AutoanalizadorConfig::carpeta());
        $realFile = realpath($ruta);

        if ($realDir === false) {
            throw new RuntimeException('Carpeta de autoanalizadores no disponible.');
        }

        if ($realFile === false || ! str_starts_with($realFile, $realDir.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('El archivo no está en la carpeta de autoanalizadores.');
        }

        if (! is_file($realFile)) {
            throw new RuntimeException('Archivo no encontrado.');
        }

        return $realFile;
    }

    public function guardarUpload(UploadedFile|TemporaryUploadedFile $archivo): string
    {
        $dir = AutoanalizadorConfig::carpeta();
        $original = $archivo->getClientOriginalName();
        $base = pathinfo($original, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if (! in_array($ext, self::EXTENSIONES, true)) {
            throw new RuntimeException('Solo se permiten archivos CSV, TXT o SHD.');
        }

        $base = preg_replace('/[^A-Za-z0-9_\-\. áéíóúÁÉÍÓÚñÑ]/u', '_', (string) $base) ?: 'export';
        $base = trim($base);
        if ($base === '') {
            $base = 'export';
        }

        $destino = $dir.DIRECTORY_SEPARATOR.$base.'.'.$ext;
        $n = 1;
        while (is_file($destino)) {
            $destino = $dir.DIRECTORY_SEPARATOR.$base.'_'.$n.'.'.$ext;
            $n++;
        }

        // TemporaryUploadedFile en Windows falla con move(); copiar es fiable.
        $tmp = $archivo->getRealPath();
        if ($tmp === false || $tmp === '') {
            throw new RuntimeException('No se pudo leer el archivo subido.');
        }

        if (! @copy($tmp, $destino)) {
            throw new RuntimeException('No se pudo guardar el archivo en AUTOANALIZADORES.');
        }

        @unlink($tmp);

        return basename($destino);
    }
}
