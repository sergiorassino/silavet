<?php

namespace App\Support;

class DniInput
{
    public static function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
