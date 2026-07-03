<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuarios')) {
            return;
        }

        if (! Schema::hasColumn('usuarios', 'permisos_ia')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->text('permisos_ia')->nullable()->after('password');
            });
        }

        if (! Schema::hasTable('permisos_ia')) {
            Schema::create('permisos_ia', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('orden')->unique();
                $table->string('tema', 80);
                $table->string('descripcion', 255);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'permisos_ia')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('permisos_ia');
            });
        }

        Schema::dropIfExists('permisos_ia');
    }
};
