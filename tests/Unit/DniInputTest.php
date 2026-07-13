<?php

namespace Tests\Unit;

use App\Support\DniInput;
use Tests\TestCase;

class DniInputTest extends TestCase
{
    public function test_normalize_permite_alfanumerico(): void
    {
        $this->assertSame('lab01', DniInput::normalize(' lab-01 '));
        $this->assertSame('25038868', DniInput::normalize('25.038.868'));
        $this->assertSame('User9', DniInput::normalize('User9'));
    }

    public function test_normalize_recorta_a_diez_caracteres(): void
    {
        $this->assertSame('1234567890', DniInput::normalize('123456789012345'));
    }
}
