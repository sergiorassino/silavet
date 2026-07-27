<?php

/**
 * Cambia `reactivoxdeterminacion.cantidad` de INT(11) a DECIMAL(10,4)
 * para permitir cantidades fraccionarias de reactivos por determinación.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * Idempotente: solo ejecuta el ALTER si la columna todavía es de tipo entero.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reactivoxdeterminacion')) {
            return;
        }

        if (! Schema::hasColumn('reactivoxdeterminacion', 'cantidad')) {
            return;
        }

        $tipo = DB::selectOne(
            "SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'reactivoxdeterminacion'
               AND COLUMN_NAME  = 'cantidad'"
        );

        // Solo modifica si la columna sigue siendo entera (int, bigint, etc.)
        if ($tipo && str_contains(strtolower((string) ($tipo->DATA_TYPE ?? '')), 'int')) {
            DB::statement(
                'ALTER TABLE `reactivoxdeterminacion`
                 MODIFY COLUMN `cantidad` DECIMAL(10,4) NOT NULL DEFAULT \'0.0000\''
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reactivoxdeterminacion')) {
            return;
        }

        if (! Schema::hasColumn('reactivoxdeterminacion', 'cantidad')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `reactivoxdeterminacion`
             MODIFY COLUMN `cantidad` INT(11) NOT NULL DEFAULT 0'
        );
    }
};
