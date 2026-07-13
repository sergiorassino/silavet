<?php

/**
 * Columna `descuento` en clientes (porcentaje de descuento del cliente veterinario).
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * En el laboratorio de referencia quedó así:
 * - clientes.descuento → decimal(6,2) NULL, después de cuit
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clientes') || Schema::hasColumn('clientes', 'descuento')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('descuento', 6, 2)->nullable()->after('cuit');
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
};
