<?php

/**
 * IVA discriminado en compafip para laboratorios responsable inscripto.
 * Los comprobantes monotributo no escriben estas columnas.
 *
 * SQL manual: database/sql/compafip_iva_responsable_inscripto.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compafip')) {
            return;
        }

        if (! Schema::hasColumn('compafip', 'impNeto')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->decimal('impNeto', 12, 2)->nullable()->default(null)->after('importe');
            });
        }

        if (! Schema::hasColumn('compafip', 'impIva')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->decimal('impIva', 12, 2)->nullable()->default(null)->after('impNeto');
            });
        }

        if (! Schema::hasColumn('compafip', 'alicuotaIva')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->decimal('alicuotaIva', 5, 2)->nullable()->default(null)->after('impIva');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('compafip')) {
            return;
        }

        foreach (['alicuotaIva', 'impIva', 'impNeto'] as $columna) {
            if (Schema::hasColumn('compafip', $columna)) {
                Schema::table('compafip', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }
    }
};
