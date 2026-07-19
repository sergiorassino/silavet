<?php

/**
 * Migración retirada: la columna `pacientes.dni` queda a cargo de
 * 2026_07_19_000004_ensure_dni_cuit_pacientes_clientes.php
 * (VARCHAR(8), junto con cuit y clientes).
 *
 * Se deja como no-op para no romper el historial de `migrations`
 * si ya se registró en algún laboratorio.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 */

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: ver 2026_07_19_000004_ensure_dni_cuit_pacientes_clientes.
    }

    public function down(): void
    {
        // No-op.
    }
};
