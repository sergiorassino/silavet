<?php

/**
 * Lista de precios por protocolo (`pacientes.listaPreciosPaciente`)
 * y por cliente (`clientes.listaPreciosCliente`).
 *
 * Aditivo: solo agrega si la tabla existe y la columna aún no está.
 * No modifica columnas legacy ya presentes (p. ej. laboratoriosiv).
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pacientes') && ! Schema::hasColumn('pacientes', 'listaPreciosPaciente')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $col = $table->unsignedTinyInteger('listaPreciosPaciente')->default(1);
                if (Schema::hasColumn('pacientes', 'propietario')) {
                    $col->after('propietario');
                }
            });
        }

        if (Schema::hasTable('clientes') && ! Schema::hasColumn('clientes', 'listaPreciosCliente')) {
            Schema::table('clientes', function (Blueprint $table) {
                $col = $table->unsignedTinyInteger('listaPreciosCliente')->default(1);
                if (Schema::hasColumn('clientes', 'descuento')) {
                    $col->after('descuento');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pacientes') && Schema::hasColumn('pacientes', 'listaPreciosPaciente')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->dropColumn('listaPreciosPaciente');
            });
        }

        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'listaPreciosCliente')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('listaPreciosCliente');
            });
        }
    }
};
