<?php

/**
 * Vínculo movimientos → protocolo (`pacientes.idPacientes`).
 * 0 = no asignado. Permite bloquear el borrado del protocolo si hay caja asociada
 * y, a futuro, asignar movimientos a un protocolo concreto.
 *
 * SQL manual: database/sql/movimientos_id_pacientes.sql
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('movimientos')) {
            return;
        }

        if (! Schema::hasColumn('movimientos', 'idPacientes')) {
            Schema::table('movimientos', function (Blueprint $table) {
                if (Schema::hasColumn('movimientos', 'idClientes')) {
                    $table->integer('idPacientes')->default(0)->after('idClientes');
                } else {
                    $table->integer('idPacientes')->default(0);
                }
                $table->index('idPacientes', 'movimientos_idPacientes_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movimientos') && Schema::hasColumn('movimientos', 'idPacientes')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->dropIndex('movimientos_idPacientes_index');
                $table->dropColumn('idPacientes');
            });
        }
    }
};
