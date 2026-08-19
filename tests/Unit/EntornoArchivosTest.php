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
}
