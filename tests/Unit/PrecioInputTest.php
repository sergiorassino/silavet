<?php

namespace Tests\Unit;

use App\Support\PrecioInput;
use PHPUnit\Framework\TestCase;

class PrecioInputTest extends TestCase
{
    public function test_format_argentino(): void
    {
        $this->assertSame('25.000,00', PrecioInput::format(25000));
        $this->assertSame('8.000,50', PrecioInput::format(8000.5));
        $this->assertSame('0,00', PrecioInput::format(null));
    }

    public function test_parse_argentino(): void
    {
        $this->assertSame(25000.0, PrecioInput::parse('25.000,00'));
        $this->assertSame(8000.5, PrecioInput::parse('8.000,50'));
        $this->assertSame(0.0, PrecioInput::parse(''));
    }
}
