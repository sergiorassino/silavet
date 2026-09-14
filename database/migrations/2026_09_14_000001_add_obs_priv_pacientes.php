<?php

/**
 * Columna `obsPriv` en pacientes (observación privada del laboratorio).
 * No se imprime en el informe; `pacientes.observaciones` sigue siendo el pie público.
 *
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
        if (Schema::hasTable('pacientes') && ! Schema::hasColumn('pacientes', 'obsPriv')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $col = $table->text('obsPriv')->nullable();
                if (Schema::hasColumn('pacientes', 'obsInterna')) {
                    $col->after('obsInterna');
                } elseif (Schema::hasColumn('pacientes', 'observaciones')) {
                    $col->after('observaciones');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pacientes') && Schema::hasColumn('pacientes', 'obsPriv')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->dropColumn('obsPriv');
            });
        }
    }
};
