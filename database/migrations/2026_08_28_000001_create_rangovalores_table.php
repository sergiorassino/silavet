<?php

/**
 * Tabla de valores de referencia por ítem, especie y sexo (hemograma auto / ABM).
 * Algunos laboratorios legacy no la traen en el dump inicial.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * SQL manual: database/sql/rangovalores.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rangovalores') || ! Schema::hasTable('itemsinforme')) {
            return;
        }

        Schema::create('rangovalores', function (Blueprint $table) {
            $table->increments('idRangovalores');
            $table->integer('idItems');
            $table->integer('idEspecies');
            $table->integer('idSexos');
            $table->decimal('valorMin', 10, 2)->nullable();
            $table->decimal('valorMax', 10, 2)->nullable();

            $table->foreign('idItems', 'fk_rangovalores_items')
                ->references('idItems')
                ->on('itemsinforme')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rangovalores');
    }
};
