<?php

/**
 * Ficha fiscal del laboratorio (ARCA/AFIP) en entorno.
 * Un CUIT, un punto de venta y los certificados del laboratorio.
 *
 * Se aplica con: php artisan migrate
 * SQL manual: database/sql/entorno_configuracion_arca.sql
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

        $this->agregar('afipCuit', function (Blueprint $table): void {
            $table->string('afipCuit', 11)->default('');
        });
        $this->agregar('afipRazonSocial', function (Blueprint $table): void {
            $table->string('afipRazonSocial', 100)->default('');
        });
        $this->agregar('afipDomicComerc', function (Blueprint $table): void {
            $table->string('afipDomicComerc', 50)->default('');
        });
        $this->agregar('afipCondIva', function (Blueprint $table): void {
            $table->string('afipCondIva', 30)->default('');
        });
        $this->agregar('afipIngresosBrutos', function (Blueprint $table): void {
            $table->string('afipIngresosBrutos', 30)->default('');
        });
        $this->agregar('afipInicioActiv', function (Blueprint $table): void {
            $table->date('afipInicioActiv')->nullable();
        });
        $this->agregar('afipPtoVta', function (Blueprint $table): void {
            $table->unsignedInteger('afipPtoVta')->default(0);
        });
        $this->agregar('afipConcepto', function (Blueprint $table): void {
            $table->unsignedTinyInteger('afipConcepto')->default(2);
        });
        $this->agregar('afipKey', function (Blueprint $table): void {
            $table->string('afipKey', 100)->default('');
        });
        $this->agregar('afipCrt', function (Blueprint $table): void {
            $table->string('afipCrt', 100)->default('');
        });
        $this->agregar('afipCrtVencimiento', function (Blueprint $table): void {
            $table->date('afipCrtVencimiento')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        foreach ([
            'afipCrtVencimiento',
            'afipCrt',
            'afipKey',
            'afipConcepto',
            'afipPtoVta',
            'afipInicioActiv',
            'afipIngresosBrutos',
            'afipCondIva',
            'afipDomicComerc',
            'afipRazonSocial',
            'afipCuit',
        ] as $columna) {
            if (Schema::hasColumn('entorno', $columna)) {
                Schema::table('entorno', function (Blueprint $table) use ($columna): void {
                    $table->dropColumn($columna);
                });
            }
        }
    }

    private function agregar(string $columna, callable $definir): void
    {
        if (Schema::hasColumn('entorno', $columna)) {
            return;
        }

        Schema::table('entorno', function (Blueprint $table) use ($definir): void {
            $definir($table);
        });
    }
};
