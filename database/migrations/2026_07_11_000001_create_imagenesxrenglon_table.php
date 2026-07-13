<?php

/**
 * Tabla de imágenes adjuntas a renglones de informe (microscopía, etc.).
 * Compatible con el blank ScriptCase subirImagen.
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imagenesxrenglon')) {
            return;
        }

        Schema::create('imagenesxrenglon', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idRenglones');
            $table->string('nombreImagen', 255);
            $table->text('observacion')->nullable();

            $table->index('idRenglones');
            $table->index('nombreImagen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenesxrenglon');
    }
};
