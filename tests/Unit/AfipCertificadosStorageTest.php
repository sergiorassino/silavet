<?php

namespace Tests\Unit;

use App\Support\Afip\AfipCertificadosStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AfipCertificadosStorageTest extends TestCase
{
    private const ID_PRUEBA = 2147483001;

    protected function tearDown(): void
    {
        $dir = AfipCertificadosStorage::directorio(self::ID_PRUEBA);
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_nombre_seguro_rechaza_reservados_y_aplana_rutas(): void
    {
        $this->assertNull(AfipCertificadosStorage::nombreSeguro('TA.xml'));
        $this->assertNull(AfipCertificadosStorage::nombreSeguro('0'));
        $this->assertNull(AfipCertificadosStorage::nombreSeguro(''));
        $this->assertSame('homo.key', AfipCertificadosStorage::nombreSeguro('homo.key'));
        $this->assertSame('cert-afip.crt', AfipCertificadosStorage::nombreSeguro('cert-afip.crt'));

        $sanitizado = AfipCertificadosStorage::nombreSeguro('../etc/passwd.key');
        $this->assertNotNull($sanitizado);
        $this->assertSame($sanitizado, basename($sanitizado));
        $this->assertStringNotContainsString('/', $sanitizado);
        $this->assertStringNotContainsString('\\', $sanitizado);
    }

    public function test_guardar_copia_a_carpeta_del_usuario(): void
    {
        $key = UploadedFile::fake()->create('Mi Empresa.key', 1);
        $crt = UploadedFile::fake()->create('Mi Empresa.crt', 1);

        $nombreKey = AfipCertificadosStorage::guardar(
            self::ID_PRUEBA,
            $key,
            AfipCertificadosStorage::TIPO_KEY,
            'keyUpload',
        );
        $nombreCrt = AfipCertificadosStorage::guardar(
            self::ID_PRUEBA,
            $crt,
            AfipCertificadosStorage::TIPO_CRT,
            'crtUpload',
        );

        $this->assertSame('Mi_Empresa.key', $nombreKey);
        $this->assertSame('Mi_Empresa.crt', $nombreCrt);
        $this->assertTrue(AfipCertificadosStorage::existe(self::ID_PRUEBA, $nombreKey));
        $this->assertTrue(AfipCertificadosStorage::existe(self::ID_PRUEBA, $nombreCrt));
    }

    public function test_invalidar_tickets_borra_ta_y_conserva_certificados(): void
    {
        $dir = AfipCertificadosStorage::directorio(self::ID_PRUEBA);
        File::ensureDirectoryExists($dir);
        File::put($dir.DIRECTORY_SEPARATOR.'homo.key', 'key');
        File::put($dir.DIRECTORY_SEPARATOR.'TA.xml', '<ta/>');
        File::put($dir.DIRECTORY_SEPARATOR.'TRA.xml', '<tra/>');

        AfipCertificadosStorage::invalidarTickets(self::ID_PRUEBA);

        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'homo.key');
        $this->assertFileDoesNotExist($dir.DIRECTORY_SEPARATOR.'TA.xml');
        $this->assertFileDoesNotExist($dir.DIRECTORY_SEPARATOR.'TRA.xml');
    }

    public function test_eliminar_obsoleto_no_borra_si_el_nombre_no_cambia(): void
    {
        $dir = AfipCertificadosStorage::directorio(self::ID_PRUEBA);
        File::ensureDirectoryExists($dir);
        File::put($dir.DIRECTORY_SEPARATOR.'viejo.key', 'a');
        File::put($dir.DIRECTORY_SEPARATOR.'nuevo.key', 'b');

        AfipCertificadosStorage::eliminarObsoleto(self::ID_PRUEBA, 'nuevo.key', 'nuevo.key');
        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'nuevo.key');

        AfipCertificadosStorage::eliminarObsoleto(self::ID_PRUEBA, 'viejo.key', 'nuevo.key');
        $this->assertFileDoesNotExist($dir.DIRECTORY_SEPARATOR.'viejo.key');
        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'nuevo.key');
    }

    public function test_eliminar_borra_el_archivo_y_rechaza_nombres_reservados(): void
    {
        $dir = AfipCertificadosStorage::directorio(self::ID_PRUEBA);
        File::ensureDirectoryExists($dir);
        File::put($dir.DIRECTORY_SEPARATOR.'homo.key', 'key');
        File::put($dir.DIRECTORY_SEPARATOR.'TA.xml', '<ta/>');

        $this->assertTrue(AfipCertificadosStorage::eliminar(self::ID_PRUEBA, 'homo.key'));
        $this->assertFileDoesNotExist($dir.DIRECTORY_SEPARATOR.'homo.key');
        $this->assertFalse(AfipCertificadosStorage::eliminar(self::ID_PRUEBA, 'TA.xml'));
        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'TA.xml');
        $this->assertFalse(AfipCertificadosStorage::eliminar(self::ID_PRUEBA, '../homo.key'));
    }
}
