<?php

/**
 * Columna `descuento` en clientes (porcentaje de descuento del cliente veterinario).
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * En el laboratorio de referencia quedó así:
 * - clientes.descuento → decimal(6,2) NULL, después de cuit
 *
 * Antes de usar after('cuit') asegura dni/cuit en clientes y pacientes
 * (no todas las BD legacy tienen esas columnas).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureVarchar('pacientes', 'dni', 8, 'propietario');
        $this->ensureVarchar('pacientes', 'cuit', 13, 'dni');
        $this->ensureVarchar('clientes', 'cuit', 13, 'whatsapp');
        $this->ensureVarchar('clientes', 'dni', 8, 'cuit');

        if (! Schema::hasTable('clientes') || Schema::hasColumn('clientes', 'descuento')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'cuit')) {
                $table->decimal('descuento', 6, 2)->nullable()->after('cuit');
            } else {
                $table->decimal('descuento', 6, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clientes') || ! Schema::hasColumn('clientes', 'descuento')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('descuento');
        });
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
