<?php

namespace Tests\Unit;

use App\Support\Entorno\EntornoArchivos;
use Tests\TestCase;

class EntornoArchivosTest extends TestCase
{
    public function test_nombre_para_bd_deja_solo_el_archivo(): void
    {
        $this->assertSame('logo.jpg', EntornoArchivos::nombreParaBd('entorno/logos/neolab/logo.jpg'));
        $this->assertSame('logo.jpg', EntornoArchivos::nombreParaBd('logo.jpg'));
        $this->assertSame('logo.jpg', EntornoArchivos::nombreParaBd('entorno\\logos\\neolab\\logo.jpg'));
        $this->assertNull(EntornoArchivos::nombreParaBd(''));
        $this->assertNull(EntornoArchivos::nombreParaBd('   '));
        $this->assertSame(
            'firma-der.jpg',
            EntornoArchivos::nombreParaBd('entorno/firmas/neolab/firma-der.jpg')
        );
    }

    public function test_nombre_original_conserva_el_archivo_subido(): void
    {
        $archivo = \Illuminate\Http\UploadedFile::fake()->image('Logo NeoLab.JPG');

        $this->assertSame('Logo NeoLab.jpg', EntornoArchivos::nombreArchivoOriginalSeguro($archivo));
    }

    public function test_nombre_original_sanea_caracteres_peligrosos(): void
    {
        $archivo = \Illuminate\Http\UploadedFile::fake()->image('a/b/mi firma?.png');

        $this->assertSame('mi firma.png', EntornoArchivos::nombreArchivoOriginalSeguro($archivo));
    }

    public function test_nombre_original_conserva_extension_jpeg(): void
    {
        $archivo = \Illuminate\Http\UploadedFile::fake()->image('marca.jpeg');

        $this->assertSame('marca.jpeg', EntornoArchivos::nombreArchivoOriginalSeguro($archivo));
        $this->assertTrue(EntornoArchivos::esExtensionImagen('jpeg'));
        $this->assertStringContainsString('.jpeg', EntornoArchivos::acceptInputImagen());
    }

    public function test_candidatos_de_nombre_corto_priorizan_carpeta_del_tenant(): void
    {
        config(['tenant.slug' => 'neolab']);

        $this->assertSame([
            'entorno/logos/neolab/logo.jpg',
            'entorno/firmas/neolab/logo.jpg',
            'entorno/lista-precios/neolab/logo.jpg',
            'logo.jpg',
        ], EntornoArchivos::candidatosRelativos('logo.jpg'));
    }

    public function test_candidatos_de_ruta_silavet_incluyen_el_path_y_el_nombre(): void
    {
        config(['tenant.slug' => 'neolab']);

        $this->assertSame([
            'entorno/logos/neolab/logo.jpg',
            'entorno/firmas/neolab/logo.jpg',
            'entorno/lista-precios/neolab/logo.jpg',
        ], EntornoArchivos::candidatosRelativos('entorno/logos/neolab/logo.jpg'));
    }

    public function test_png_sin_chunk_iccp_elimina_el_perfil(): void
    {
        $png = $this->pngMinimoConIccp();

        $this->assertStringContainsString('iCCP', $png);

        $limpio = EntornoArchivos::pngSinChunkIccp($png);

        $this->assertNotNull($limpio);
        $this->assertStringNotContainsString('iCCP', $limpio);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $limpio);
        $this->assertStringContainsString('IEND', $limpio);
    }

    public function test_sanear_png_en_disco_reescribe_sin_iccp(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vl_png_'.uniqid('', true);
        mkdir($dir);
        $ruta = $dir.DIRECTORY_SEPARATOR.'logo.png';
        file_put_contents($ruta, $this->pngMinimoConIccp());

        EntornoArchivos::sanearArchivoImagenEnDisco($ruta);

        $bin = (string) file_get_contents($ruta);
        $this->assertStringNotContainsString('iCCP', $bin);
        $this->assertSame($ruta, EntornoArchivos::prepararRutaParaTcpdf($ruta));

        @unlink($ruta);
        @rmdir($dir);
    }

    /**
     * PNG 1×1 con chunk iCCP sintético (el contenido del perfil no importa).
     */
    private function pngMinimoConIccp(): string
    {
        $sig = "\x89PNG\r\n\x1a\n";

        $ihdrData = pack('NN', 1, 1)."\x08\x02\x00\x00\x00";
        $ihdr = $this->pngChunk('IHDR', $ihdrData);

        // Perfil falso: nombre + flag + "datos"
        $iccpData = "fake\x00\x00"."profile-bytes";
        $iccp = $this->pngChunk('iCCP', $iccpData);

        // IDAT de un PNG 1×1 RGB válido generado por GD si está disponible.
        $idat = $this->pngChunk('IDAT', "\x08\xd7\x63\xf8\xcf\xc0\x00\x00\x03\x01\x01\x00\x18\xdd\x8d\xb4");
        $iend = $this->pngChunk('IEND', '');

        return $sig.$ihdr.$iccp.$idat.$iend;
    }

    private function pngChunk(string $type, string $data): string
    {
        $len = pack('N', strlen($data));
        $crc = pack('N', crc32($type.$data));

        return $len.$type.$data.$crc;
    }
}
