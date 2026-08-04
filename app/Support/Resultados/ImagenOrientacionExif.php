<?php

namespace App\Support\Resultados;

/**
 * Corrige rotación/espejo de fotos según EXIF Orientation.
 *
 * Los navegadores respetan el tag; TCPDF dibuja los píxeles crudos y las imágenes
 * (sobre todo de celular) aparecen rotadas en el informe PDF.
 *
 * No requiere la extensión PHP `exif`: parsea el tag en JPEG APP1.
 */
final class ImagenOrientacionExif
{
    /**
     * Ruta lista para TCPDF con orientación ya aplicada a los píxeles.
     *
     * @return array{0: string, 1: bool} [ruta, esTemporal]
     */
    public static function rutaParaPdf(string $ruta): array
    {
        $orientacion = self::leerOrientacion($ruta);
        if ($orientacion <= 1 || $orientacion > 8) {
            return [$ruta, false];
        }

        $temporal = self::escribirCorregida($ruta, $orientacion);
        if ($temporal === null) {
            return [$ruta, false];
        }

        return [$temporal, true];
    }

    /**
     * Reescribe el archivo in-place con la orientación aplicada (y sin tag EXIF).
     * Útil al subir a REPOSITORIO para que UI y PDF coincidan.
     */
    public static function normalizarArchivo(string $ruta): bool
    {
        $orientacion = self::leerOrientacion($ruta);
        if ($orientacion <= 1 || $orientacion > 8) {
            return false;
        }

        $temporal = self::escribirCorregida($ruta, $orientacion);
        if ($temporal === null) {
            return false;
        }

        $ok = @rename($temporal, $ruta);
        if (! $ok) {
            $ok = @copy($temporal, $ruta);
            @unlink($temporal);
        }

        return $ok && is_file($ruta);
    }

    public static function leerOrientacion(string $ruta): int
    {
        if ($ruta === '' || ! is_file($ruta) || ! is_readable($ruta)) {
            return 1;
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($ruta, 'IFD0', false);
            if (is_array($exif) && isset($exif['Orientation'])) {
                $o = (int) $exif['Orientation'];

                return ($o >= 1 && $o <= 8) ? $o : 1;
            }
        }

        return self::orientacionJpegManual($ruta);
    }

    private static function orientacionJpegManual(string $ruta): int
    {
        $fh = @fopen($ruta, 'rb');
        if ($fh === false) {
            return 1;
        }

        try {
            $soi = fread($fh, 2);
            if ($soi !== "\xFF\xD8") {
                return 1;
            }

            while (! feof($fh)) {
                $marker = fread($fh, 2);
                if ($marker === false || strlen($marker) < 2) {
                    break;
                }
                if ($marker[0] !== "\xFF") {
                    break;
                }

                $tipo = ord($marker[1]);
                // EOI / SOS: no hay más metadatos útiles.
                if ($tipo === 0xD9 || $tipo === 0xDA) {
                    break;
                }
                // Marcadores sin longitud.
                if ($tipo === 0x01 || ($tipo >= 0xD0 && $tipo <= 0xD7)) {
                    continue;
                }

                $lenBin = fread($fh, 2);
                if ($lenBin === false || strlen($lenBin) < 2) {
                    break;
                }
                $len = unpack('n', $lenBin)[1];
                if ($len < 2) {
                    break;
                }

                $payloadLen = $len - 2;
                $payload = $payloadLen > 0 ? fread($fh, $payloadLen) : '';
                if ($payload === false || strlen($payload) < $payloadLen) {
                    break;
                }

                // APP1 Exif
                if ($tipo === 0xE1 && str_starts_with($payload, "Exif\0\0")) {
                    return self::orientacionDesdeTiff(substr($payload, 6));
                }
            }
        } finally {
            fclose($fh);
        }

        return 1;
    }

    private static function orientacionDesdeTiff(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        $endian = substr($tiff, 0, 2);
        $little = $endian === 'II';
        if (! $little && $endian !== 'MM') {
            return 1;
        }

        $u16 = static function (int $off) use ($tiff, $little): ?int {
            if ($off < 0 || $off + 2 > strlen($tiff)) {
                return null;
            }
            $raw = substr($tiff, $off, 2);

            return $little ? unpack('v', $raw)[1] : unpack('n', $raw)[1];
        };
        $u32 = static function (int $off) use ($tiff, $little): ?int {
            if ($off < 0 || $off + 4 > strlen($tiff)) {
                return null;
            }
            $raw = substr($tiff, $off, 4);

            return $little ? unpack('V', $raw)[1] : unpack('N', $raw)[1];
        };

        $ifd0 = $u32(4);
        if ($ifd0 === null) {
            return 1;
        }

        $num = $u16($ifd0);
        if ($num === null) {
            return 1;
        }

        for ($i = 0; $i < $num; $i++) {
            $entry = $ifd0 + 2 + ($i * 12);
            $tag = $u16($entry);
            if ($tag === null) {
                break;
            }
            // 0x0112 = Orientation (SHORT)
            if ($tag === 0x0112) {
                $val = $u16($entry + 8);
                if ($val === null) {
                    return 1;
                }

                return ($val >= 1 && $val <= 8) ? $val : 1;
            }
        }

        return 1;
    }

    private static function escribirCorregida(string $ruta, int $orientacion): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagerotate')) {
            return null;
        }

        $bin = @file_get_contents($ruta);
        if ($bin === false || $bin === '') {
            return null;
        }

        $src = @imagecreatefromstring($bin);
        if ($src === false) {
            return null;
        }

        $img = self::aplicarOrientacion($src, $orientacion);
        if ($img !== $src) {
            imagedestroy($src);
        }

        $info = @getimagesize($ruta);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $tmp = tempnam(sys_get_temp_dir(), 'vl_img_');
        if ($tmp === false) {
            imagedestroy($img);

            return null;
        }
        // tempnam crea archivo sin extensión; TCPDF usa la extensión / mime.
        $destino = $tmp.'.'.$ext;
        @unlink($tmp);

        $ok = match ($ext) {
            'png' => imagepng($img, $destino, 6),
            'webp' => function_exists('imagewebp') && imagewebp($img, $destino, 85),
            'gif' => imagegif($img, $destino),
            default => imagejpeg($img, $destino, 90),
        };

        imagedestroy($img);

        if (! $ok || ! is_file($destino)) {
            @unlink($destino);

            return null;
        }

        return $destino;
    }

    /**
     * @param  \GdImage  $img
     * @return \GdImage
     */
    private static function aplicarOrientacion($img, int $orientacion)
    {
        // Ángulo de imagerotate = sentido antihorario.
        switch ($orientacion) {
            case 2:
                imageflip($img, IMG_FLIP_HORIZONTAL);

                return $img;
            case 3:
                $rotado = imagerotate($img, 180, 0);

                return $rotado !== false ? $rotado : $img;
            case 4:
                imageflip($img, IMG_FLIP_VERTICAL);

                return $img;
            case 5:
                imageflip($img, IMG_FLIP_HORIZONTAL);
                $rotado = imagerotate($img, 270, 0);

                return $rotado !== false ? $rotado : $img;
            case 6:
                $rotado = imagerotate($img, 270, 0);

                return $rotado !== false ? $rotado : $img;
            case 7:
                imageflip($img, IMG_FLIP_HORIZONTAL);
                $rotado = imagerotate($img, 90, 0);

                return $rotado !== false ? $rotado : $img;
            case 8:
                $rotado = imagerotate($img, 90, 0);

                return $rotado !== false ? $rotado : $img;
            default:
                return $img;
        }
    }
}
