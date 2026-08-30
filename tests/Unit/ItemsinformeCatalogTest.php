<?php

namespace Tests\Unit;

use App\Support\Itemsinforme\ItemsinformeCatalog;
use Tests\TestCase;

class ItemsinformeCatalogTest extends TestCase
{
    public function test_campos_editables_incluye_mostrar_si_no(): void
    {
        $campos = ItemsinformeCatalog::camposEditables();

        $this->assertArrayHasKey('mostrar', $campos);
        $this->assertSame('mostrar', $campos['mostrar']['columna']);
        $this->assertSame('select_sino', $campos['mostrar']['tipo']);
    }

    public function test_grilla_incluye_columna_mostrar(): void
    {
        $this->assertSame(17, ItemsinformeCatalog::columnasVisibles());
    }

    public function test_referencias_por_especie_permiten_varchar_200(): void
    {
        $campos = ItemsinformeCatalog::camposEditables();

        foreach (['ref_caninos', 'ref_felinos', 'ref_equinos', 'ref_porcinos', 'ref_bovinos'] as $campo) {
            $this->assertSame(200, $campos[$campo]['max']);
            $this->assertSame('textarea', $campos[$campo]['tipo']);
        }
    }

    public function test_modos_carga_incluye_formula_dos_valores(): void
    {
        $modos = ItemsinformeCatalog::modosCarga();

        $this->assertArrayHasKey(ItemsinformeCatalog::TIPO_FORMULA_DOS_VALORES, $modos);
        $this->assertSame('Fórmula dos valores', $modos[ItemsinformeCatalog::TIPO_FORMULA_DOS_VALORES]);
    }

    public function test_es_formula_dos_valores_tipo_6_y_legacy_tipo_9_sin_actualiza(): void
    {
        $this->assertTrue(ItemsinformeCatalog::esFormulaDosValores(6));
        $this->assertTrue(ItemsinformeCatalog::esFormulaDosValores(9, 0));
        $this->assertFalse(ItemsinformeCatalog::esFormulaDosValores(9, 1));
        $this->assertFalse(ItemsinformeCatalog::esFormulaDosValores(1));
    }
}
