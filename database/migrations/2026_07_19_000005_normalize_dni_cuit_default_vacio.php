<?php

/**
 * Normaliza default y valores sentinel de dni/cuit ('' en lugar de 0).
 * Para laboratorios donde ya se aplicó 2026_07_19_000004 con datos '0'.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{0: string, 1: string, 2: int}> */
    private const COLUMNAS = [
        ['pacientes', 'dni', 8],
        ['pacientes', 'cuit', 11],
        ['clientes', 'cuit', 11],
        ['clientes', 'dni', 8],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as [$table, $column, $length]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR({$length}) NOT NULL DEFAULT ''"
            );

            DB::table($table)
                ->whereIn($column, ['0', 0])
                ->update([$column => '']);
        }
    }

    public function down(): void
    {
        // No-op: no reinstaurar default 0.
    }
};
