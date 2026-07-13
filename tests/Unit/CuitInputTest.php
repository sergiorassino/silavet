<?php

namespace Tests\Unit;

use App\Support\CuitInput;
use Tests\TestCase;

class CuitInputTest extends TestCase
{
    public function test_normalize_extrae_solo_digitos(): void
    {
        $this->assertSame('30717420699', CuitInput::normalize('30-717420699'));
        $this->assertSame('30717420699', CuitInput::normalize('30 71742069 9'));
    }

    public function test_format_aplica_mascara_argentina(): void
    {
        $this->assertSame('30', CuitInput::format('30'));
        $this->assertSame('30-71742069', CuitInput::format('3071742069'));
        $this->assertSame('30-71742069-9', CuitInput::format('30717420699'));
    }

    public function test_is_complete(): void
    {
        $this->assertTrue(CuitInput::isComplete('30-71742069-9'));
        $this->assertFalse(CuitInput::isComplete('30-71742069'));
    }
}
