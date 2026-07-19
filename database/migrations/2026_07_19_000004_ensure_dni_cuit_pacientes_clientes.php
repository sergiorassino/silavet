<?php

/**
 * Asegura `dni` VARCHAR(8) y `cuit` VARCHAR(11) en `pacientes` y `clientes`.
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * Idempotente:
 * - Si la columna no existe → la agrega con DEFAULT '' (nunca 0).
 * - Si existe con otro tipo/longitud o default distinto de '' → MODIFY.
 * - Valores sentinel '0' (vacío legacy) → ''.
 *
 * No elimina columnas en down(): pueden ser legacy preexistentes.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureVarchar('pacientes', 'dni', 8, 'propietario');
        $this->ensureVarchar('pacientes', 'cuit', 11, 'dni');
        $this->ensureVarchar('clientes', 'cuit', 11, 'whatsapp');
        $this->ensureVarchar('clientes', 'dni', 8, 'cuit');
    }

    public function down(): void
    {
        // Intencionalmente vacío: dni/cuit pueden existir en BD legacy
        // o haber sido creados por migraciones anteriores.
    }

    private function ensureVarchar(string $table, string $column, int $length, ?string $after = null): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, $column)) {
            $afterSql = ($after !== null && Schema::hasColumn($table, $after))
                ? " AFTER `{$after}`"
                : '';
            DB::statement(
                "ALTER TABLE `{$table}` ADD COLUMN `{$column}` VARCHAR({$length}) NOT NULL DEFAULT ''{$afterSql}"
            );
            $this->limpiarCerosSentinel($table, $column);

            return;
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
            && (int) $meta->CHARACTER_MAXIMUM_LENGTH === $length
            && strtoupper((string) $meta->IS_NULLABLE) === 'NO';

        if (! $typeOk || ! $defaultOk) {
            DB::statement(
                "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR({$length}) NOT NULL DEFAULT ''"
            );
        }

        $this->limpiarCerosSentinel($table, $column);
    }

    /** NeoLab suele guardar CUIT/DNI vacío como '0'; deja '' para formularios opcionales. */
    private function limpiarCerosSentinel(string $table, string $column): void
    {
        DB::table($table)
            ->whereIn($column, ['0', 0])
            ->update([$column => '']);
    }
};
