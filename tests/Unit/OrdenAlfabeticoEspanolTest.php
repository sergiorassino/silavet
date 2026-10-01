<?php

namespace Tests\Unit;

use App\Support\OrdenAlfabeticoEspanol;
use PHPUnit\Framework\TestCase;

class OrdenAlfabeticoEspanolTest extends TestCase
{
    public function test_acentos_van_con_su_vocal_no_al_final(): void
    {
        $nombres = ['Zoo', 'Úrico', 'Ácido biliar', 'Albumina', 'Urea'];
        usort($nombres, [OrdenAlfabeticoEspanol::class, 'comparar']);

        $this->assertSame([
            'Ácido biliar',
            'Albumina',
            'Urea',
            'Úrico',
            'Zoo',
        ], $nombres);
    }

    public function test_enie_va_despues_de_n_y_antes_de_o(): void
    {
        $nombres = ['Oseo', 'Ñandú', 'Nitrógeno', 'Albumina'];
        usort($nombres, [OrdenAlfabeticoEspanol::class, 'comparar']);

        $this->assertSame([
            'Albumina',
            'Nitrógeno',
            'Ñandú',
            'Oseo',
        ], $nombres);
    }

    public function test_mayusculas_no_alteran_el_orden(): void
    {
        $this->assertSame(0, OrdenAlfabeticoEspanol::comparar('urea', 'UREA'));
        $this->assertLessThan(0, OrdenAlfabeticoEspanol::comparar('ácido', 'Zoo'));
    }

    public function test_busqueda_ignora_acentos_y_conserva_enie(): void
    {
        $this->assertTrue(OrdenAlfabeticoEspanol::contiene('Perfil Básico', 'basico'));
        $this->assertTrue(OrdenAlfabeticoEspanol::contiene('Ácido biliar', 'acido'));
        $this->assertTrue(OrdenAlfabeticoEspanol::contiene('Ácido biliar', 'ÁCIDO'));
        $this->assertTrue(OrdenAlfabeticoEspanol::contiene('Niño', 'niño'));
        $this->assertFalse(OrdenAlfabeticoEspanol::contiene('Niño', 'nino'));
        $this->assertFalse(OrdenAlfabeticoEspanol::contiene('Urea', 'urico'));
        $this->assertTrue(OrdenAlfabeticoEspanol::contiene('Urea', ''));
    }
}
