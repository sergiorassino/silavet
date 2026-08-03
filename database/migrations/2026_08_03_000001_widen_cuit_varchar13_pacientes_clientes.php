<?php

/**
 * Amplía `cuit` a VARCHAR(13) en pacientes y clientes si quedó en 11
 * (migraciones 2026_07_19_000004 / 000005 ya aplicadas).
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * Idempotente: no-op si ya es VARCHAR(13) NOT NULL DEFAULT ''.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{0: string, 1: string}> */
    private const COLUMNAS = [
        ['pacientes', 'cuit'],
        ['clientes', 'cuit'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $meta = DB::selectOne(
                'SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT, IS_NULLABLE
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?',
                [$table, $column]
            );

            $defaultOk = $meta
                && $meta->COLUMN_DEFAULT !== null
                && (string) $meta->COLUMN_DEFAULT === '';
            $typeOk = $meta
                && strtolower((string) $meta->DATA_TYPE) === 'varchar'
                && (int) $meta->CHARACTER_MAXIMUM_LENGTH === 13
                && strtoupper((string) $meta->IS_NULLABLE) === 'NO';

            if (! $typeOk || ! $defaultOk) {
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR(13) NOT NULL DEFAULT ''"
                );
            }
        }
    }

    public function down(): void
    {
        // No-op: no volver a VARCHAR(11).
    }
};
