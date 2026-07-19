<?php

/**
 * Parámetros de etiquetas térmicas (tubos) en entorno (columnas e_*).
 * Se aplica con: php artisan lb:migrate-legacy --force
 * Multi-labo (todas las BD lb_*): database/sql/entorno_parametros_etiquetas.sql
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

        Schema::table('entorno', function (Blueprint $table) {
            $columnas = [
                'e_AnchoPapel' => fn () => $table->decimal('e_AnchoPapel', 8, 2)->nullable()->default(80),
                'e_AnchoEtiq' => fn () => $table->decimal('e_AnchoEtiq', 8, 2)->nullable()->default(35),
                'e_AltoEtiq' => fn () => $table->decimal('e_AltoEtiq', 8, 2)->nullable()->default(20),
                'e_CantCol' => fn () => $table->unsignedTinyInteger('e_CantCol')->nullable()->default(2),
                'e_GapX' => fn () => $table->decimal('e_GapX', 8, 2)->nullable()->default(2),
                'e_GapY' => fn () => $table->decimal('e_GapY', 8, 2)->nullable()->default(2),
                'e_MarginTop' => fn () => $table->decimal('e_MarginTop', 8, 2)->nullable()->default(1),
                'e_MarginBottom' => fn () => $table->decimal('e_MarginBottom', 8, 2)->nullable()->default(0),
                'e_MarginLeft' => fn () => $table->decimal('e_MarginLeft', 8, 2)->nullable()->default(2),
                'e_MarginRight' => fn () => $table->decimal('e_MarginRight', 8, 2)->nullable()->default(0),
                'e_FontLinea1' => fn () => $table->unsignedTinyInteger('e_FontLinea1')->nullable()->default(18),
                'e_FontLinea2' => fn () => $table->unsignedTinyInteger('e_FontLinea2')->nullable()->default(12),
                'e_FontLinea3' => fn () => $table->unsignedTinyInteger('e_FontLinea3')->nullable()->default(11),
                'e_FontLinea4' => fn () => $table->unsignedTinyInteger('e_FontLinea4')->nullable()->default(8),
                'e_MaxLargoLinea2' => fn () => $table->unsignedTinyInteger('e_MaxLargoLinea2')->nullable()->default(21),
                'e_MaxLargoLinea3' => fn () => $table->unsignedTinyInteger('e_MaxLargoLinea3')->nullable()->default(25),
                'e_Borde' => fn () => $table->boolean('e_Borde')->nullable()->default(false),
            ];

            foreach ($columnas as $nombre => $definicion) {
                if (! Schema::hasColumn('entorno', $nombre)) {
                    $definicion();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        $columnas = [
            'e_AnchoPapel', 'e_AnchoEtiq', 'e_AltoEtiq', 'e_CantCol',
            'e_GapX', 'e_GapY',
            'e_MarginTop', 'e_MarginBottom', 'e_MarginLeft', 'e_MarginRight',
            'e_FontLinea1', 'e_FontLinea2', 'e_FontLinea3', 'e_FontLinea4',
            'e_MaxLargoLinea2', 'e_MaxLargoLinea3', 'e_Borde',
        ];

        Schema::table('entorno', function (Blueprint $table) use ($columnas) {
            foreach ($columnas as $nombre) {
                if (Schema::hasColumn('entorno', $nombre)) {
                    $table->dropColumn($nombre);
                }
            }
        });
    }
};
