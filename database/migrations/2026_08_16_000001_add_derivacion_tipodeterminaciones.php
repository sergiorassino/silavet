<?php

/**
 * Columna derivacion en tipodeterminaciones (FK al catálogo de centros).
 * El sistema legacy guardó el centro predeterminado aquí, no en destino.
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipodeterminaciones')) {
            return;
        }

        if (Schema::hasColumn('tipodeterminaciones', 'derivacion')) {
            return;
        }

        $afterPerfil = Schema::hasColumn('tipodeterminaciones', 'perfil');

        Schema::table('tipodeterminaciones', function (Blueprint $table) use ($afterPerfil) {
            $columna = $table->integer('derivacion')->default(0);
            if ($afterPerfil) {
                $columna->after('perfil');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tipodeterminaciones')) {
            return;
        }

        if (Schema::hasColumn('tipodeterminaciones', 'derivacion')) {
            Schema::table('tipodeterminaciones', function (Blueprint $table) {
                $table->dropColumn('derivacion');
            });
        }
    }
};
