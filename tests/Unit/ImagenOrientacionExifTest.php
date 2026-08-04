<?php

namespace Tests\Unit;

use App\Support\Resultados\ImagenOrientacionExif;
use Tests\TestCase;

class ImagenOrientacionExifTest extends TestCase
{
    public function test_lee_orientacion_exif_sin_extension_exif(): void
    {
        $ruta = $this->jpegConOrientacion(6, 40, 20);
        try {
            $this->assertSame(6, ImagenOrientacionExif::leerOrientacion($ruta));
        } finally {
            @unlink($ruta);
        }
    }

    public function test_ruta_para_pdf_aplica_rotacion_90_cw(): void
    {
        $ruta = $this->jpegConOrientacion(6, 40, 20);
        $temporal = null;
        try {
            [$rutaPdf, $esTemporal] = ImagenOrientacionExif::rutaParaPdf($ruta);
            if ($esTemporal) {
                $temporal = $rutaPdf;
            }

            $this->assertTrue($esTemporal);
            $this->assertNotSame($ruta, $rutaPdf);
            $this->assertFileExists($rutaPdf);

            $info = getimagesize($rutaPdf);
            $this->assertIsArray($info);
            // Orientation 6 = 90° CW → 40×20 pasa a 20×40.
            $this->assertSame(20, (int) $info[0]);
            $this->assertSame(40, (int) $info[1]);
            $this->assertSame(1, ImagenOrientacionExif::leerOrientacion($rutaPdf));
        } finally {
            @unlink($ruta);
            if (is_string($temporal)) {
                @unlink($temporal);
            }
        }
    }

    public function test_normalizar_archivo_reescribe_in_place(): void
    {
        $ruta = $this->jpegConOrientacion(8, 30, 10);
        try {
            $this->assertTrue(ImagenOrientacionExif::normalizarArchivo($ruta));
            $info = getimagesize($ruta);
            $this->assertIsArray($info);
            // Orientation 8 = 90° CCW → 30×10 pasa a 10×30.
            $this->assertSame(10, (int) $info[0]);
            $this->assertSame(30, (int) $info[1]);
            $this->assertSame(1, ImagenOrientacionExif::leerOrientacion($ruta));
        } finally {
            @unlink($ruta);
        }
    }

    public function test_sin_exif_devuelve_ruta_original(): void
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vl_exif_plain_'.uniqid('', true).'.jpg';
        $img = imagecreatetruecolor(16, 8);
        imagejpeg($img, $ruta, 90);
        imagedestroy($img);

        try {
            [$rutaPdf, $esTemporal] = ImagenOrientacionExif::rutaParaPdf($ruta);
            $this->assertSame($ruta, $rutaPdf);
            $this->assertFalse($esTemporal);
        } finally {
            @unlink($ruta);
        }
    }

    /**
     * JPEG mínimo con APP1 Exif Orientation (little-endian TIFF IFD0).
     */
    private function jpegConOrientacion(int $orientacion, int $ancho, int $alto): string
    {
        $img = imagecreatetruecolor($ancho, $alto);
        $rojo = imagecolorallocate($img, 220, 40, 40);
        imagefilledrectangle($img, 0, 0, $ancho - 1, $alto - 1, $rojo);
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vl_exif_raw_'.uniqid('', true).'.jpg';
        imagejpeg($img, $tmp, 90);
        imagedestroy($img);

        $jpeg = (string) file_get_contents($tmp);
        @unlink($tmp);

        // APP1 Exif: "Exif\0\0" + TIFF IFD0 con tag Orientation.
        // TIFF LE: II + magic 42 + IFD offset 8
        // IFD: 1 entry + next IFD 0
        // Entry: tag 0x0112, type SHORT(3), count 1, value = orientacion
        $tiff = "II".pack('v', 42).pack('V', 8);
        $tiff .= pack('v', 1);
        $tiff .= pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', $orientacion).pack('v', 0);
        $tiff .= pack('V', 0);

        $exifPayload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($exifPayload) + 2).$exifPayload;

        // Insertar APP1 justo después del SOI (FF D8).
        $this->assertSame("\xFF\xD8", substr($jpeg, 0, 2));
        $conExif = "\xFF\xD8".$app1.substr($jpeg, 2);

        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vl_exif_o'.$orientacion.'_'.uniqid('', true).'.jpg';
        file_put_contents($ruta, $conExif);

        return $ruta;
    }
}
