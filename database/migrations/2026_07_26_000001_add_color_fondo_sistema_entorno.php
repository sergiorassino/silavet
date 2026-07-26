<?php

/**
 * Color de fondo del sistema (UI web) en entorno.
 * Se aplica con: php artisan lb:migrate-legacy --force
 * SQL manual multi-labo: database/sql/entorno_color_fondo_sistema.sql
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

        if (! Schema::hasColumn('entorno', 'colorFondoSistema')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->string('colorFondoSistema', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        if (Schema::hasColumn('entorno', 'colorFondoSistema')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->dropColumn('colorFondoSistema');
            });
        }
    }
};
