<?php

/**
 * Columna grupos.mostrarReferencias: 1 = imprimir encabezado
 * «VALORES DE REFERENCIA» en el PDF del informe; 0 = no.
 * Se aplica con: php artisan lb:migrate-legacy --force
 *
 * El grupo OBSERVACIONES sigue sin encabezado por código, aunque el flag sea 1.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grupos')) {
            return;
        }

        if (! Schema::hasColumn('grupos', 'mostrarReferencias')) {
            Schema::table('grupos', function (Blueprint $table) {
                $col = $table->tinyInteger('mostrarReferencias')->default(1);
                if (Schema::hasColumn('grupos', 'orden')) {
                    $col->after('orden');
                }
            });
        }

        DB::table('grupos')
            ->where(function ($q) {
                $q->whereRaw('UPPER(nombreGrupo) = ?', ['OBSERVACIONES'])
                    ->orWhereRaw('UPPER(nombreGrupo) = ?', ['INFORME DE ECOGRAFÍA']);
            })
            ->update(['mostrarReferencias' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('grupos') || ! Schema::hasColumn('grupos', 'mostrarReferencias')) {
            return;
        }

        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn('mostrarReferencias');
        });
    }
};
