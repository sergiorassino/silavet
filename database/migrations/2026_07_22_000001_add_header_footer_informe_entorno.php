<?php

/**
 * Imágenes opcionales de encabezado y pie del informe de protocolo.
 * Si están cargadas, reemplazan el membrete (logo + contacto) y el pie de firmas.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * SQL manual multi-labo: database/sql/entorno_header_footer_informe.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        if (! Schema::hasColumn('entorno', 'headerInforme')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->string('headerInforme', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('entorno', 'footerInforme')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->string('footerInforme', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        if (Schema::hasColumn('entorno', 'headerInforme')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->dropColumn('headerInforme');
            });
        }

        if (Schema::hasColumn('entorno', 'footerInforme')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->dropColumn('footerInforme');
            });
        }
    }
};
