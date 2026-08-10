<?php

/**
 * Amplía clientes.email a VARCHAR(500) para varios destinatarios (separados por ;).
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * Idempotente: no-op si ya es VARCHAR(500) NOT NULL DEFAULT ''.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clientes') || ! Schema::hasColumn('clientes', 'email')) {
            return;
        }

        $meta = DB::selectOne(
            'SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT, IS_NULLABLE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['clientes', 'email']
        );

        $defaultOk = $meta
            && $meta->COLUMN_DEFAULT !== null
            && (string) $meta->COLUMN_DEFAULT === '';
        $typeOk = $meta
            && strtolower((string) $meta->DATA_TYPE) === 'varchar'
            && (int) $meta->CHARACTER_MAXIMUM_LENGTH >= 500
            && strtoupper((string) $meta->IS_NULLABLE) === 'NO';

        if (! $typeOk || ! $defaultOk) {
            DB::statement(
                "ALTER TABLE `clientes` MODIFY COLUMN `email` VARCHAR(500) NOT NULL DEFAULT ''"
            );
        }
    }

    public function down(): void
    {
        // No-op: no volver a VARCHAR(150).
    }
};
