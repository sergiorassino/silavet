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
}
