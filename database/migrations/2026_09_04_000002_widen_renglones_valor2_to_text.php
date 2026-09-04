<?php

/**
 * Cambia `renglones.valor2` de VARCHAR(100) a TEXT (igual que `valor`).
 * Conserva charset/collation y el contenido existente.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * Idempotente: no-op si ya es TEXT (o medium/long/tinytext).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const YA_TEXTO = ['text', 'tinytext', 'mediumtext', 'longtext'];

    public function up(): void
    {
        if (! Schema::hasTable('renglones') || ! Schema::hasColumn('renglones', 'valor2')) {
            return;
        }

        $meta = DB::selectOne(
            'SELECT DATA_TYPE, CHARACTER_SET_NAME, COLLATION_NAME, IS_NULLABLE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['renglones', 'valor2']
        );

        if ($meta === null) {
            return;
        }

        $tipo = strtolower((string) ($meta->DATA_TYPE ?? ''));
        if (in_array($tipo, self::YA_TEXTO, true)) {
            return;
        }

        $charset = $this->identificadorSql((string) ($meta->CHARACTER_SET_NAME ?? ''), 'utf8mb3');
        $collation = $this->identificadorSql((string) ($meta->COLLATION_NAME ?? ''), 'utf8mb3_spanish_ci');
        $nulo = strtoupper((string) ($meta->IS_NULLABLE ?? 'YES')) === 'NO'
            ? 'NOT NULL'
            : 'NULL DEFAULT NULL';

        DB::statement(
            "ALTER TABLE `renglones` MODIFY COLUMN `valor2` text CHARACTER SET {$charset} COLLATE {$collation} {$nulo}"
        );
    }

    public function down(): void
    {
        // No-op: no volver a VARCHAR(100); podría truncar observaciones ya guardadas.
    }

    private function identificadorSql(string $valor, string $fallback): string
    {
        $limpio = preg_replace('/[^a-zA-Z0-9_]/', '', $valor) ?? '';

        return $limpio !== '' ? $limpio : $fallback;
    }
};
