<?php

/**
 * Facturación AFIP:
 * - entorno.afipFormatoImpresion (A4 | termica80)
 * - compafip.idCompAfipAsoc (vínculo NC → factura)
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * SQL manual multi-labo: database/sql/facturacion_afip_entorno_compafip.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entorno') && ! Schema::hasColumn('entorno', 'afipFormatoImpresion')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->string('afipFormatoImpresion', 20)->default('A4');
            });
        }

        if (Schema::hasTable('compafip') && ! Schema::hasColumn('compafip', 'idCompAfipAsoc')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->unsignedInteger('idCompAfipAsoc')->nullable();
                $table->index('idCompAfipAsoc', 'compafip_idCompAfipAsoc_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('entorno') && Schema::hasColumn('entorno', 'afipFormatoImpresion')) {
            Schema::table('entorno', function (Blueprint $table) {
                $table->dropColumn('afipFormatoImpresion');
            });
        }

        if (Schema::hasTable('compafip') && Schema::hasColumn('compafip', 'idCompAfipAsoc')) {
            Schema::table('compafip', function (Blueprint $table) {
                $table->dropIndex('compafip_idCompAfipAsoc_index');
                $table->dropColumn('idCompAfipAsoc');
            });
        }
    }
};
