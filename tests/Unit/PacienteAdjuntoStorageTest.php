<?php

namespace Tests\Unit;

use App\Support\Protocolos\PacienteAdjuntoStorage;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PacienteAdjuntoStorageTest extends TestCase
{
    private const NOMBRE_PRUEBA = '_silavet_test_BM117- MILO SUAREZ - qPCR.pdf';

    protected function tearDown(): void
    {
        $ruta = PacienteAdjuntoStorage::directorioAbsoluto().DIRECTORY_SEPARATOR.self::NOMBRE_PRUEBA;
        if (is_file($ruta)) {
            File::delete($ruta);
        }

        parent::tearDown();
    }

    public function test_nombre_seguro_acepta_espacios_y_ruta_relativa_neolab(): void
    {
        $conEspacios = 'BM117- MILO SUAREZ - qPCR LEISHMANIA.docx.pdf';

        $this->assertSame($conEspacios, PacienteAdjuntoStorage::nombreSeguro($conEspacios));
        $this->assertSame(
            '20260822_092153_475.pdf',
            PacienteAdjuntoStorage::nombreSeguro('adjuntos/20260822_092153_475.pdf')
        );
        $this->assertSame(
            '20260822_092153_475.pdf',
            PacienteAdjuntoStorage::nombreSeguro('adjuntos\\20260822_092153_475.pdf')
        );
        $this->assertSame(
            $conEspacios,
            PacienteAdjuntoStorage::nombreSeguro('../adjuntos/'.$conEspacios)
        );
    }

    public function test_nombre_seguro_rechaza_vacios_y_no_pdf(): void
    {
        $this->assertNull(PacienteAdjuntoStorage::nombreSeguro(''));
        $this->assertNull(PacienteAdjuntoStorage::nombreSeguro('   '));
        $this->assertNull(PacienteAdjuntoStorage::nombreSeguro('.'));
        $this->assertNull(PacienteAdjuntoStorage::nombreSeguro('..'));
        $this->assertNull(PacienteAdjuntoStorage::nombreSeguro('informe.docx'));
        $this->assertNull(PacienteAdjuntoStorage::nombreSeguro('informe'));
    }

    public function test_ruta_si_existe_resuelve_nombre_con_espacios_y_prefijo(): void
    {
        File::ensureDirectoryExists(PacienteAdjuntoStorage::directorioAbsoluto());
        $destino = PacienteAdjuntoStorage::rutaAbsoluta(self::NOMBRE_PRUEBA);
        $this->assertNotFalse(file_put_contents($destino, "%PDF-1.4\n"));

        $this->assertNotNull(PacienteAdjuntoStorage::rutaSiExiste(self::NOMBRE_PRUEBA));
        $this->assertNotNull(PacienteAdjuntoStorage::rutaSiExiste('adjuntos/'.self::NOMBRE_PRUEBA));
        $this->assertTrue(PacienteAdjuntoStorage::existe('../adjuntos/'.self::NOMBRE_PRUEBA));
        $this->assertNull(PacienteAdjuntoStorage::rutaSiExiste('no-existe-silavet.pdf'));
    }
}
