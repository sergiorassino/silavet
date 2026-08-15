<?php

namespace App\Support\Afip;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

/**
 * Certificados AFIP por usuario: afipSE/cert/{idUsuarios}/{nombre}.
 * En BD solo se persiste el nombre de archivo (usuarios.key / usuarios.crt).
 */
final class AfipCertificadosStorage
{
    public const TIPO_KEY = 'key';

    public const TIPO_CRT = 'crt';

    /** @var list<string> */
    public const EXT_KEY = ['key', 'pem'];

    /** @var list<string> */
    public const EXT_CRT = ['crt', 'cer', 'pem'];

    public const MAX_KB = 256;

    public const MAX_NOMBRE = 100;

    public static function directorio(int $idUsuarios): string
    {
        if ($idUsuarios <= 0) {
            throw new RuntimeException('El usuario no tiene identificador para guardar certificados AFIP.');
        }

        return base_path('afipSE'.DIRECTORY_SEPARATOR.'cert'.DIRECTORY_SEPARATOR.(string) $idUsuarios);
    }

    public static function rutaAbsoluta(int $idUsuarios, string $nombre): ?string
    {
        $seguro = self::nombreSeguro($nombre);
        if ($seguro === null) {
            return null;
        }

        return self::directorio($idUsuarios).DIRECTORY_SEPARATOR.$seguro;
    }

    public static function existe(int $idUsuarios, string $nombre): bool
    {
        $ruta = self::rutaAbsoluta($idUsuarios, $nombre);
        if ($ruta === null) {
            return false;
        }

        $real = realpath($ruta);
        $dir = realpath(self::directorio($idUsuarios));
        if ($real === false || $dir === false || ! is_file($real)) {
            return false;
        }

        return self::rutaEstaDentro($real, $dir);
    }

    /**
     * @param  list<string>  $extensiones
     */
    public static function extensionPermitida(string $extension, array $extensiones): bool
    {
        return in_array(strtolower($extension), $extensiones, true);
    }

    public static function nombreSeguro(string $nombre): ?string
    {
        $nombre = basename(str_replace(["\0", '\\', '/'], '', $nombre));
        if ($nombre === '' || $nombre === '.' || $nombre === '..' || $nombre === '0') {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $nombre)) {
            return null;
        }

        if (strlen($nombre) > self::MAX_NOMBRE) {
            return null;
        }

        $reservados = ['ta.xml', 'tra.xml', '.gitkeep'];
        if (in_array(strtolower($nombre), $reservados, true)) {
            return null;
        }

        return $nombre;
    }

    /**
     * Copia el upload a afipSE/cert/{idUsuarios}/ y devuelve el nombre a persistir en BD.
     */
    public static function guardar(
        int $idUsuarios,
        UploadedFile|TemporaryUploadedFile $archivo,
        string $tipo,
        string $campoError,
    ): string {
        $extensiones = $tipo === self::TIPO_KEY ? self::EXT_KEY : self::EXT_CRT;
        $extension = strtolower($archivo->getClientOriginalExtension() ?: '');
        if (! self::extensionPermitida($extension, $extensiones)) {
            throw ValidationException::withMessages([
                $campoError => $tipo === self::TIPO_KEY
                    ? 'La clave privada debe ser un archivo .key o .pem.'
                    : 'El certificado debe ser un archivo .crt, .cer o .pem.',
            ]);
        }

        $nombre = self::nombreDesdeUpload($archivo, $tipo);
        $dir = self::directorio($idUsuarios);
        File::ensureDirectoryExists($dir);
        self::assertDirectorioDelUsuario($dir, $idUsuarios);

        $destino = $dir.DIRECTORY_SEPARATOR.$nombre;
        $origen = $archivo->getRealPath();
        // Livewire TemporaryUploadedFile en Windows falla con move(); copiar es fiable.
        if ($origen === false || ! is_file($origen)) {
            throw ValidationException::withMessages([
                $campoError => 'No se pudo leer el archivo temporal subido.',
            ]);
        }

        if (! File::copy($origen, $destino) || ! is_file($destino)) {
            throw ValidationException::withMessages([
                $campoError => 'No se pudo guardar el certificado en afipSE/cert/'.$idUsuarios.'. Verifique permisos de escritura.',
            ]);
        }

        @chmod($destino, 0600);

        return $nombre;
    }

    /**
     * Borra un certificado de la carpeta del usuario. No toca TA/TRA ni nombres inseguros.
     */
    public static function eliminar(int $idUsuarios, string $nombre): bool
    {
        $seguro = self::nombreSeguro($nombre);
        if ($seguro === null || ! self::existe($idUsuarios, $seguro)) {
            return false;
        }

        $ruta = self::rutaAbsoluta($idUsuarios, $seguro);
        if ($ruta === null) {
            return false;
        }

        return File::delete($ruta);
    }

    public static function eliminarObsoleto(int $idUsuarios, string $nombreAnterior, string $nombreNuevo): void
    {
        $anterior = self::nombreSeguro($nombreAnterior);
        $nuevo = self::nombreSeguro($nombreNuevo);
        if ($anterior === null || $anterior === $nuevo) {
            return;
        }

        self::eliminar($idUsuarios, $anterior);
    }

    public static function tieneColumnaVencimiento(): bool
    {
        return Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'crtVencimiento');
    }

    /**
     * Fecha de vencimiento (Y-m-d) leída del X.509. Null si no es un certificado válido.
     */
    public static function vencimientoDesdeUpload(UploadedFile|TemporaryUploadedFile $archivo): ?string
    {
        $ruta = $archivo->getRealPath();
        if ($ruta === false || ! is_file($ruta)) {
            return null;
        }

        return self::vencimientoDesdeRuta($ruta);
    }

    public static function vencimientoDesdeRuta(string $ruta): ?string
    {
        $contenido = @file_get_contents($ruta);
        if ($contenido === false || $contenido === '') {
            return null;
        }

        return self::vencimientoDesdeContenido($contenido);
    }

    public static function vencimientoDesdeContenido(string $contenido): ?string
    {
        $pem = self::contenidoAPem($contenido);
        if ($pem === null) {
            return null;
        }

        $info = @openssl_x509_parse($pem);
        if (! is_array($info)) {
            return null;
        }

        $ts = (int) ($info['validTo_time_t'] ?? 0);
        if ($ts > 0) {
            return gmdate('Y-m-d', $ts);
        }

        $asn1 = (string) ($info['validTo'] ?? '');
        if ($asn1 === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('ymdHis\Z', $asn1, new \DateTimeZone('UTC'));
        if ($dt === false) {
            return null;
        }

        return $dt->format('Y-m-d');
    }

    /**
     * Invalida tickets WSAA al reemplazar certificados (fuerza un nuevo loginCms).
     */
    public static function invalidarTickets(int $idUsuarios): void
    {
        $dir = self::directorio($idUsuarios);
        if (! is_dir($dir)) {
            return;
        }

        foreach (File::files($dir) as $archivo) {
            $nombre = strtolower($archivo->getFilename());
            if (str_starts_with($nombre, 'ta') && str_ends_with($nombre, '.xml')) {
                File::delete($archivo->getPathname());
            }
            if (str_starts_with($nombre, 'tra') && str_ends_with($nombre, '.xml')) {
                File::delete($archivo->getPathname());
            }
        }
    }

    private static function contenidoAPem(string $contenido): ?string
    {
        $trim = trim($contenido);
        if ($trim === '') {
            return null;
        }

        if (str_contains($trim, 'BEGIN CERTIFICATE')) {
            return $trim;
        }

        $encoded = chunk_split(base64_encode($contenido), 64, "\n");

        return "-----BEGIN CERTIFICATE-----\n".$encoded."-----END CERTIFICATE-----\n";
    }

    private static function nombreDesdeUpload(UploadedFile|TemporaryUploadedFile $archivo, string $tipo): string
    {
        $original = $archivo->getClientOriginalName();
        $base = pathinfo($original, PATHINFO_FILENAME);
        $ext = strtolower($archivo->getClientOriginalExtension() ?: '');
        $base = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $base) ?? '';
        $base = trim($base, '._-');
        if ($base === '') {
            $base = $tipo === self::TIPO_KEY ? 'clave' : 'certificado';
        }

        $nombre = $base.'.'.$ext;
        if (strlen($nombre) > self::MAX_NOMBRE) {
            $corte = self::MAX_NOMBRE - strlen($ext) - 1;
            $nombre = substr($base, 0, max(1, $corte)).'.'.$ext;
        }

        $seguro = self::nombreSeguro($nombre);
        if ($seguro === null) {
            throw ValidationException::withMessages([
                $tipo === self::TIPO_KEY ? 'keyUpload' : 'crtUpload' => 'El nombre del archivo no es válido.',
            ]);
        }

        return $seguro;
    }

    private static function assertDirectorioDelUsuario(string $dir, int $idUsuarios): void
    {
        $root = realpath(base_path('afipSE'.DIRECTORY_SEPARATOR.'cert'));
        $realDir = realpath($dir);
        if ($root === false || $realDir === false) {
            throw new RuntimeException('No se pudo resolver la carpeta de certificados AFIP.');
        }

        if (! self::rutaEstaDentro($realDir, $root) && $realDir !== $root) {
            throw new RuntimeException('La carpeta de certificados no pertenece a afipSE/cert.');
        }

        $esperado = realpath(self::directorio($idUsuarios));
        if ($esperado === false || $realDir !== $esperado) {
            throw new RuntimeException('La carpeta de certificados no coincide con el usuario.');
        }
    }

    private static function rutaEstaDentro(string $ruta, string $contenedor): bool
    {
        $norm = static fn (string $p): string => strtolower(rtrim(str_replace('\\', '/', $p), '/'));
        $rutaN = $norm($ruta);
        $contN = $norm($contenedor);

        return $rutaN === $contN || str_starts_with($rutaN, $contN.'/');
    }
}
