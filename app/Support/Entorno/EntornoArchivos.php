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

    /** @var list<string> */
    public const CAMPOS_NOMBRE_ARCHIVO_NEOLAB = ['logo', 'firmaIzq', 'firmaCentro', 'firmaDer'];

    /** @var list<string> */
    public const EXTENSIONES_IMAGEN = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

    public static function acceptInputImagen(): string
    {
        return '.jpeg,.jpg,.png,.gif,.webp,image/jpeg,image/pjpeg,image/jpg,image/png,image/gif,image/webp';
    }

    /**
     * @return list<string>
     */
    public static function reglasArchivoImagen(int $maxKb): array
    {
        return [
            'nullable',
            'file',
            'mimes:'.implode(',', self::EXTENSIONES_IMAGEN),
            'max:'.$maxKb,
        ];
    }

    public static function esExtensionImagen(string $ext): bool
    {
        return in_array(strtolower($ext), self::EXTENSIONES_IMAGEN, true);
    }

    /**
     * Valor a persistir en logo/firmas de NeoLab: solo el nombre de archivo, sin ruta.
     */
    public static function nombreParaBd(?string $ruta): ?string
    {
        $ruta = str_replace('\\', '/', trim((string) $ruta));
        if ($ruta === '') {
            return null;
        }

        $nombre = basename($ruta);
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            return null;
        }

        if (str_contains($nombre, '/') || str_contains($nombre, '\\')) {
            return null;
        }

        return $nombre;
    }

    public static function esCampoNombreArchivoNeoLab(string $campo): bool
    {
        return in_array($campo, self::CAMPOS_NOMBRE_ARCHIVO_NEOLAB, true);
    }

    /**
     * Nombre del archivo subido, sanitizado, con extensión de imagen válida.
     */
    public static function nombreArchivoOriginalSeguro(UploadedFile $archivo): string
    {
        $ext = strtolower($archivo->getClientOriginalExtension() ?: '');
        if (! self::esExtensionImagen($ext)) {
            $ext = 'jpg';
        }

        $crudo = self::nombreParaBd($archivo->getClientOriginalName()) ?? '';
        $base = pathinfo($crudo, PATHINFO_FILENAME);
        $base = preg_replace('/[^\p{L}\p{N}._\- ]+/u', '', $base) ?? '';
        $base = trim((string) preg_replace('/\s+/u', ' ', $base));
        $base = trim($base, '._ ');
        if ($base === '' || str_starts_with($base, '.')) {
            $base = 'archivo';
        }

        $nombre = $base.'.'.$ext;
        if (strlen($nombre) > 180) {
            $nombre = substr($base, 0, 170).'.'.$ext;
        }

        return $nombre;
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
        if (! self::esExtensionImagen($extension)) {
            $extension = 'png';
        }

        return self::guardarArchivo($archivo, $directorio, $nombreBase.'.'.$extension);
    }

    /**
     * Guarda la imagen con el nombre original (sanitizado) y borra el archivo anterior si cambió.
     */
    public static function guardarImagenConNombreOriginal(
        UploadedFile $archivo,
        string $directorio,
        ?string $valorAnterior = null,
    ): string {
        $nombre = self::nombreArchivoOriginalSeguro($archivo);
        $anterior = self::nombreParaBd($valorAnterior);
        if ($anterior !== null && strcasecmp($anterior, $nombre) !== 0) {
            self::eliminarArchivoSiExiste($valorAnterior);
        }

        return self::guardarArchivo($archivo, $directorio, $nombre);
    }

    public static function eliminarArchivoSiExiste(?string $valor): void
    {
        $abs = self::rutaAbsoluta($valor);
        if ($abs === null || ! is_file($abs)) {
            return;
        }

        $raiz = realpath(public_path('entorno'));
        $real = realpath($abs);
        if ($raiz === false || $real === false || ! str_starts_with($real, $raiz)) {
            return;
        }

        @unlink($real);

        $nombre = self::nombreParaBd($valor);
        $legacyDir = self::directorioLegacyAbsoluto();
        if ($nombre !== null && $legacyDir !== null) {
            $legacy = $legacyDir.DIRECTORY_SEPARATOR.$nombre;
            if (is_file($legacy)) {
                @unlink($legacy);
            }
        }
    }

    /**
     * Copia el archivo de SILAVET a la carpeta de imágenes de NeoLab (si está configurada).
     */
    public static function espejarHaciaLegacy(string $rutaRelativaPublic, ?string $nombreBd = null): void
    {
        $nombre = $nombreBd ?? self::nombreParaBd($rutaRelativaPublic);
        $origen = self::rutaAbsoluta($rutaRelativaPublic);
        $destinoDir = self::directorioLegacyConfigurado();

        if ($nombre === null || $origen === null || $destinoDir === null) {
            return;
        }

        File::ensureDirectoryExists($destinoDir);
        $destino = $destinoDir.DIRECTORY_SEPARATOR.$nombre;
        File::copy($origen, $destino);
        @chmod($destino, 0644);
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

        // PNG con perfil iCCP inválido hace fallar TCPDF (GD/libpng).
        self::sanearArchivoImagenEnDisco($destino);

        return $directorio.'/'.$nombreArchivo;
    }

    /**
     * Prepara una imagen absoluta para usarla en TCPDF (sanea PNG problemáticos).
     */
    public static function prepararRutaParaTcpdf(?string $rutaAbsoluta): ?string
    {
        if ($rutaAbsoluta === null || $rutaAbsoluta === '' || ! is_file($rutaAbsoluta)) {
            return null;
        }

        self::sanearArchivoImagenEnDisco($rutaAbsoluta);

        return is_file($rutaAbsoluta) ? $rutaAbsoluta : null;
    }

    /**
     * Quita perfiles ICC inválidos de PNG (warning iCCP → TCPDF ERROR).
     * Idempotente: si no hay iCCP, no reescribe el archivo.
     */
    public static function sanearArchivoImagenEnDisco(string $rutaAbsoluta): void
    {
        if (! is_file($rutaAbsoluta) || ! is_readable($rutaAbsoluta)) {
            return;
        }

        $ext = strtolower(pathinfo($rutaAbsoluta, PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            return;
        }

        $bin = @file_get_contents($rutaAbsoluta);
        if ($bin === false || $bin === '') {
            return;
        }

        $limpio = self::pngSinChunkIccp($bin);
        if ($limpio === null || $limpio === $bin) {
            return;
        }

        $tmp = $rutaAbsoluta.'.vl-san.tmp';
        if (@file_put_contents($tmp, $limpio) === false) {
            return;
        }

        if (! @rename($tmp, $rutaAbsoluta)) {
            @unlink($tmp);

            return;
        }

        @chmod($rutaAbsoluta, 0644);
    }

    /**
     * Reconstruye un PNG omitiendo el chunk iCCP (perfil de color incorrecto).
     */
    public static function pngSinChunkIccp(string $bin): ?string
    {
        $sig = "\x89PNG\r\n\x1a\n";
        if (! str_starts_with($bin, $sig)) {
            return null;
        }

        if (! str_contains($bin, 'iCCP')) {
            return $bin;
        }

        $out = $sig;
        $offset = 8;
        $len = strlen($bin);
        $vioIend = false;

        while ($offset + 12 <= $len) {
            $lengthData = unpack('N', substr($bin, $offset, 4));
            if ($lengthData === false) {
                return null;
            }

            $length = (int) $lengthData[1];
            if ($length < 0 || $offset + 12 + $length > $len) {
                return null;
            }

            $type = substr($bin, $offset + 4, 4);
            $chunkTotal = 12 + $length;

            if ($type !== 'iCCP') {
                $out .= substr($bin, $offset, $chunkTotal);
            }

            $offset += $chunkTotal;

            if ($type === 'IEND') {
                $vioIend = true;
                break;
            }
        }

        return $vioIend ? $out : null;
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

    /**
     * Ruta relativa a public/ que existe (acepta nombre solo o ruta SILAVET).
     *
     * @return list<string>
     */
    public static function candidatosRelativos(?string $rutaRelativa): array
    {
        $ruta = str_replace('\\', '/', trim((string) $rutaRelativa));
        if ($ruta === '') {
            return [];
        }

        $nombre = self::nombreParaBd($ruta);
        $out = [];

        if (! str_contains($ruta, '/')) {
            if ($nombre !== null) {
                $out[] = self::directorioLogo().'/'.$nombre;
                $out[] = self::directorioFirmas().'/'.$nombre;
                $out[] = self::directorioListaPrecios().'/'.$nombre;
                $out[] = $nombre;
            }
        } else {
            $out[] = $ruta;
            if ($nombre !== null) {
                $out[] = self::directorioLogo().'/'.$nombre;
                $out[] = self::directorioFirmas().'/'.$nombre;
                $out[] = self::directorioListaPrecios().'/'.$nombre;
            }
        }

        return array_values(array_unique($out));
    }

    public static function rutaRelativaEnPublic(?string $rutaRelativa): ?string
    {
        foreach (self::candidatosRelativos($rutaRelativa) as $candidato) {
            if (is_file(public_path($candidato))) {
                return $candidato;
            }
        }

        $ruta = str_replace('\\', '/', trim((string) $rutaRelativa));
        if (str_starts_with($ruta, 'storage/')) {
            $enStorage = storage_path('app/public/'.substr($ruta, strlen('storage/')));
            if (is_file($enStorage)) {
                return $ruta;
            }
        }

        return null;
    }

    public static function rutaAbsoluta(?string $rutaRelativa): ?string
    {
        $rel = self::rutaRelativaEnPublic($rutaRelativa);
        if ($rel !== null) {
            if (str_starts_with($rel, 'storage/')) {
                $enStorage = storage_path('app/public/'.substr($rel, strlen('storage/')));
                if (is_file($enStorage)) {
                    return $enStorage;
                }
            }

            $directa = public_path($rel);
            if (is_file($directa)) {
                return $directa;
            }
        }

        return self::rutaAbsolutaEnLegacy($rutaRelativa);
    }

    public static function urlPublica(?string $rutaRelativa, bool $cacheBust = false): ?string
    {
        $rel = self::rutaRelativaEnPublic($rutaRelativa);
        if ($rel === null) {
            $rel = self::importarDesdeLegacy($rutaRelativa);
        }

        if ($rel === null || str_starts_with($rel, 'storage/')) {
            return null;
        }

        $absoluta = public_path($rel);
        if (! is_file($absoluta)) {
            return null;
        }

        $url = asset($rel);

        if ($cacheBust) {
            $url .= '?v='.filemtime($absoluta);
        }

        return $url;
    }

    private static function importarDesdeLegacy(?string $rutaRelativa): ?string
    {
        $nombre = self::nombreParaBd($rutaRelativa);
        $origen = self::rutaAbsolutaEnLegacy($nombre);
        if ($nombre === null || $origen === null) {
            return null;
        }

        $relEncontrada = null;
        foreach ([self::directorioLogo(), self::directorioFirmas()] as $dir) {
            $rel = $dir.'/'.$nombre;
            $destino = public_path($rel);
            File::ensureDirectoryExists(dirname($destino));
            if (! is_file($destino)) {
                File::copy($origen, $destino);
                @chmod($destino, 0644);
            }
            if ($relEncontrada === null && is_file($destino)) {
                $relEncontrada = $rel;
            }
        }

        return $relEncontrada;
    }

    private static function rutaAbsolutaEnLegacy(?string $rutaRelativa): ?string
    {
        $nombre = self::nombreParaBd($rutaRelativa);
        $dir = self::directorioLegacyAbsoluto();
        if ($nombre === null || $dir === null) {
            return null;
        }

        $abs = $dir.DIRECTORY_SEPARATOR.$nombre;

        return is_file($abs) ? $abs : null;
    }

    private static function directorioLegacyAbsoluto(): ?string
    {
        $dir = self::directorioLegacyConfigurado();
        if ($dir === null) {
            return null;
        }

        $real = realpath($dir);

        return ($real !== false && is_dir($real)) ? $real : null;
    }

    private static function directorioLegacyConfigurado(): ?string
    {
        $dir = trim((string) config('tenant.institucional.logo_legacy_dir', ''));
        if ($dir === '') {
            return null;
        }

        if (! self::esRutaAbsoluta($dir)) {
            $dir = base_path($dir);
        }

        return rtrim($dir, '/\\');
    }

    private static function esRutaAbsoluta(string $dir): bool
    {
        return str_starts_with($dir, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $dir) === 1;
    }
}
