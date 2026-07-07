<?php

/**
 * Columna listaPreciosPdf en entorno (PDF para autogestión del cliente).
 * Se aplica con: php artisan lb:migrate-legacy --force
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

        if (! Schema::hasColumn('entorno', 'listaPreciosPdf')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->string('listaPreciosPdf', 255)->nullable()->after('nombreListaPrecio');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        if (Schema::hasColumn('entorno', 'listaPreciosPdf')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->dropColumn('listaPreciosPdf');
            });
        }
    }
};
