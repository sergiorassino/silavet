<?php

/**
 * Columnas de lista de precios en entorno (nombre + PDF para autogestión).
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * Idempotente: no usa AFTER si la columna ancla no existe (dumps legacy
 * incompletos, p. ej. sin nombreListaPrecio).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        $this->addColumnIfMissing(
            'nombreListaPrecio',
            '`nombreListaPrecio` varchar(200) DEFAULT NULL',
            'formulas'
        );
        $this->addColumnIfMissing(
            'listaPreciosPdf',
            '`listaPreciosPdf` varchar(255) DEFAULT NULL',
            'nombreListaPrecio'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        if (Schema::hasColumn('entorno', 'listaPreciosPdf')) {
            Schema::table('entorno', function ($table) {
                $table->dropColumn('listaPreciosPdf');
            });
        }
    }

    private function addColumnIfMissing(string $name, string $ddl, ?string $after): void
    {
        if (Schema::hasColumn('entorno', $name)) {
            return;
        }

        $afterSql = ($after !== null && Schema::hasColumn('entorno', $after))
            ? " AFTER `{$after}`"
            : '';

        DB::statement("ALTER TABLE `entorno` ADD COLUMN {$ddl}{$afterSql}");
    }
};
