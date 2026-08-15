<?php

/**
 * Fecha de vencimiento del certificado AFIP (.crt) del emisor.
 * Se completa al cargar el archivo en Gestión de Usuarios.
 *
 * SQL manual: database/sql/usuarios_crt_vencimiento.sql
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

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

        if (! Schema::hasColumn('usuarios', 'crtVencimiento')) {
            Schema::table('usuarios', function (Blueprint $table) {
                if (Schema::hasColumn('usuarios', 'crt')) {
                    $table->date('crtVencimiento')->nullable()->after('crt');
                } else {
                    $table->date('crtVencimiento')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'crtVencimiento')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('crtVencimiento');
            });
        }
    }
};
