<?php

/**
 * Columna `neto` en pacientes y determinaciones.
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * En el laboratorio de referencia quedó así:
 * - pacientes.neto      → antes de precio (después de estado)
 * - determinaciones.neto → antes de precio (después de idTipodeterminaciones)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pacientes') && ! Schema::hasColumn('pacientes', 'neto')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->decimal('neto', 20, 2)->default(0)->after('estado');
            });
        }

        if (Schema::hasTable('determinaciones') && ! Schema::hasColumn('determinaciones', 'neto')) {
            Schema::table('determinaciones', function (Blueprint $table) {
                $table->decimal('neto', 20, 2)->default(0)->after('idTipodeterminaciones');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pacientes') && Schema::hasColumn('pacientes', 'neto')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->dropColumn('neto');
            });
        }

        if (Schema::hasTable('determinaciones') && Schema::hasColumn('determinaciones', 'neto')) {
            Schema::table('determinaciones', function (Blueprint $table) {
                $table->dropColumn('neto');
            });
        }
    }
};
