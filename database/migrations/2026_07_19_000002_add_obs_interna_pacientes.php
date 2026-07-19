<?php

/**
 * Columna `obsInterna` en pacientes (observaciones internas del protocolo).
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * Solo se agrega si la tabla existe y la columna aún no está.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pacientes') && ! Schema::hasColumn('pacientes', 'obsInterna')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->text('obsInterna')->nullable()->after('observaciones');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pacientes') && Schema::hasColumn('pacientes', 'obsInterna')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->dropColumn('obsInterna');
            });
        }
    }
};
