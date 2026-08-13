<?php

/**
 * Fecha/hora real de carga del movimiento (alta), distinta de `fechhora`
 * (fecha de negocio, que puede ser anterior).
 *
 * SQL manual: database/sql/movimientos_fechhora_carga.sql
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('movimientos')) {
            return;
        }

        if (! Schema::hasColumn('movimientos', 'fechhoraCarga')) {
            Schema::table('movimientos', function (Blueprint $table) {
                if (Schema::hasColumn('movimientos', 'fechhora')) {
                    $table->dateTime('fechhoraCarga')->nullable()->after('fechhora');
                } else {
                    $table->dateTime('fechhoraCarga')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movimientos') && Schema::hasColumn('movimientos', 'fechhoraCarga')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->dropColumn('fechhoraCarga');
            });
        }
    }
};
