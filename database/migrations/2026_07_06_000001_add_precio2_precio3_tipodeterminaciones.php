<?php

/**
 * Columnas precio2 y precio3 en tipodeterminaciones.
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

        if (! Schema::hasColumn('tipodeterminaciones', 'precio2')) {
            Schema::table('tipodeterminaciones', function (Blueprint $table) {
                $table->decimal('precio2', 20, 2)->default(0)->after('precio');
            });
        }

        if (! Schema::hasColumn('tipodeterminaciones', 'precio3')) {
            Schema::table('tipodeterminaciones', function (Blueprint $table) {
                $table->decimal('precio3', 20, 2)->default(0)->after('precio2');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tipodeterminaciones')) {
            return;
        }

        if (Schema::hasColumn('tipodeterminaciones', 'precio3')) {
            Schema::table('tipodeterminaciones', function (Blueprint $table) {
                $table->dropColumn('precio3');
            });
        }

        if (Schema::hasColumn('tipodeterminaciones', 'precio2')) {
            Schema::table('tipodeterminaciones', function (Blueprint $table) {
                $table->dropColumn('precio2');
            });
        }
    }
};
