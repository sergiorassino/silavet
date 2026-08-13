<?php

/**
 * Vínculo compafip → movimientos (tesoreria_pacientes / labvetciudad).
 *
 * SQL manual: database/sql/compafip_id_movimientos.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compafip') && ! Schema::hasColumn('compafip', 'idMovimientos')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->unsignedInteger('idMovimientos')->nullable()->default(null);
                $table->index('idMovimientos', 'compafip_idMovimientos_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compafip') && Schema::hasColumn('compafip', 'idMovimientos')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->dropIndex('compafip_idMovimientos_index');
                $table->dropColumn('idMovimientos');
            });
        }
    }
};
