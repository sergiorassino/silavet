<?php

/**
 * Columnas adicionales en entorno para Parámetros del Sistema.
 * Se aplica con: php artisan lb:migrate-legacy --force
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
                'logo' => fn () => $table->string('logo', 255)->nullable(),
                'fondo' => fn () => $table->string('fondo', 255)->nullable(),
                'direLabo' => fn () => $table->string('direLabo', 255)->nullable(),
                'teleLabo' => fn () => $table->string('teleLabo', 80)->nullable(),
                'emailLabo' => fn () => $table->string('emailLabo', 120)->nullable(),
                'colorInforme' => fn () => $table->string('colorInforme', 20)->nullable(),
                'texto1footerIzq' => fn () => $table->string('texto1footerIzq', 255)->nullable(),
                'texto2footerIzq' => fn () => $table->string('texto2footerIzq', 255)->nullable(),
                'texto1footerCentro' => fn () => $table->string('texto1footerCentro', 255)->nullable(),
                'texto2footerCentro' => fn () => $table->string('texto2footerCentro', 255)->nullable(),
                'texto1footerDer' => fn () => $table->string('texto1footerDer', 255)->nullable(),
                'texto2footerDer' => fn () => $table->string('texto2footerDer', 255)->nullable(),
                'firmaIzq' => fn () => $table->string('firmaIzq', 255)->nullable(),
                'firmaCentro' => fn () => $table->string('firmaCentro', 255)->nullable(),
                'firmaDer' => fn () => $table->string('firmaDer', 255)->nullable(),
                'ctaEnvioMail' => fn () => $table->string('ctaEnvioMail', 120)->nullable(),
                'passEnvioMail' => fn () => $table->string('passEnvioMail', 255)->nullable(),
                'fromMail' => fn () => $table->string('fromMail', 120)->nullable(),
                'nombrePieMail' => fn () => $table->string('nombrePieMail', 120)->nullable(),
                'direccionPieMail' => fn () => $table->string('direccionPieMail', 255)->nullable(),
                'telefonoPieMail' => fn () => $table->string('telefonoPieMail', 80)->nullable(),
                'emailPieMail' => fn () => $table->string('emailPieMail', 120)->nullable(),
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
            'logo', 'fondo', 'direLabo', 'teleLabo', 'emailLabo', 'colorInforme',
            'texto1footerIzq', 'texto2footerIzq', 'texto1footerCentro', 'texto2footerCentro',
            'texto1footerDer', 'texto2footerDer', 'firmaIzq', 'firmaCentro', 'firmaDer',
            'ctaEnvioMail', 'passEnvioMail', 'fromMail', 'nombrePieMail',
            'direccionPieMail', 'telefonoPieMail', 'emailPieMail',
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
