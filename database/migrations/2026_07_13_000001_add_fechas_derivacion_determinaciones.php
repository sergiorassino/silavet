<?php

/**
 * Fechas de envío y devolución de determinaciones derivadas.
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('determinaciones')) {
            return;
        }

        if (! Schema::hasColumn('determinaciones', 'fechaEnvioDeriv')) {
            Schema::table('determinaciones', function (Blueprint $table) {
                $col = $table->date('fechaEnvioDeriv')->nullable();
                if (Schema::hasColumn('determinaciones', 'idDerivaciones')) {
                    $col->after('idDerivaciones');
                }
            });
        }

        if (! Schema::hasColumn('determinaciones', 'fechaDevolucDeterm')) {
            Schema::table('determinaciones', function (Blueprint $table) {
                $col = $table->date('fechaDevolucDeterm')->nullable();
                if (Schema::hasColumn('determinaciones', 'fechaEnvioDeriv')) {
                    $col->after('fechaEnvioDeriv');
                } elseif (Schema::hasColumn('determinaciones', 'idDerivaciones')) {
                    $col->after('idDerivaciones');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('determinaciones')) {
            return;
        }

        Schema::table('determinaciones', function (Blueprint $table) {
            if (Schema::hasColumn('determinaciones', 'fechaDevolucDeterm')) {
                $table->dropColumn('fechaDevolucDeterm');
            }

            if (Schema::hasColumn('determinaciones', 'fechaEnvioDeriv')) {
                $table->dropColumn('fechaEnvioDeriv');
            }
        });
    }
};
