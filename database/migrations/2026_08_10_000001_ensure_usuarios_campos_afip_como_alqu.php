<?php

/**
 * Alinea `usuarios` con la estructura de referencia de lb_alqu
 * (idRoles + campos AFIP). Algunos labs no tienen idRoles ni los campos AFIP.
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * (o php artisan migrate --force sobre el tenant activo)
 *
 * Idempotente: solo ADD COLUMN / INDEX si falta. No modifica tipos de columnas
 * ya existentes ni borra nada en down() (pueden ser legacy preexistentes).
 *
 * SQL manual: database/sql/usuarios_campos_afip_como_alqu.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas presentes en lb_alqu.usuarios que pueden faltar en otros labs.
     * Definición MySQL idéntica a SHOW FULL COLUMNS de lb_alqu.
     *
     * @var list<array{name: string, ddl: string, after: string}>
     */
    private const COLUMNAS = [
        [
            'name' => 'idRoles',
            'ddl' => '`idRoles` int(11) DEFAULT NULL',
            'after' => 'idClientes',
        ],
        [
            'name' => 'permisoAfip',
            'ddl' => '`permisoAfip` int(1) NOT NULL DEFAULT 0',
            'after' => 'permisos_ia',
        ],
        [
            'name' => 'cuit',
            'ddl' => "`cuit` varchar(11) NOT NULL DEFAULT '0'",
            'after' => 'permisoAfip',
        ],
        [
            'name' => 'razonSocial',
            'ddl' => "`razonSocial` varchar(100) NOT NULL DEFAULT '0'",
            'after' => 'cuit',
        ],
        [
            'name' => 'domicComerc',
            'ddl' => "`domicComerc` varchar(50) NOT NULL DEFAULT '0'",
            'after' => 'razonSocial',
        ],
        [
            'name' => 'condIva',
            'ddl' => "`condIva` varchar(30) NOT NULL DEFAULT '0'",
            'after' => 'domicComerc',
        ],
        [
            'name' => 'ingresosBrutos',
            'ddl' => "`ingresosBrutos` varchar(30) NOT NULL DEFAULT '0'",
            'after' => 'condIva',
        ],
        [
            'name' => 'inicioActiv',
            'ddl' => '`inicioActiv` date DEFAULT NULL',
            'after' => 'ingresosBrutos',
        ],
        [
            'name' => 'PtoVta',
            'ddl' => '`PtoVta` int(2) NOT NULL DEFAULT 0',
            'after' => 'inicioActiv',
        ],
        [
            'name' => 'CbteTipo',
            'ddl' => '`CbteTipo` int(2) NOT NULL DEFAULT 0',
            'after' => 'PtoVta',
        ],
        [
            'name' => 'NtaCredTipo',
            'ddl' => '`NtaCredTipo` int(2) NOT NULL DEFAULT 0',
            'after' => 'CbteTipo',
        ],
        [
            'name' => 'Concepto',
            'ddl' => '`Concepto` int(2) NOT NULL DEFAULT 0',
            'after' => 'NtaCredTipo',
        ],
        [
            'name' => 'DocTipo',
            'ddl' => '`DocTipo` int(2) NOT NULL DEFAULT 0',
            'after' => 'Concepto',
        ],
        [
            'name' => 'CondicionIVAReceptorId',
            'ddl' => '`CondicionIVAReceptorId` int(2) NOT NULL DEFAULT 0',
            'after' => 'DocTipo',
        ],
        [
            'name' => 'key',
            'ddl' => "`key` varchar(100) NOT NULL DEFAULT '0'",
            'after' => 'CondicionIVAReceptorId',
        ],
        [
            'name' => 'crt',
            'ddl' => "`crt` varchar(100) NOT NULL DEFAULT '0'",
            'after' => 'key',
        ],
    ];

    /** @var array<string, true> */
    private array $columnasConocidas = [];

    public function up(): void
    {
        if (! Schema::hasTable('usuarios')) {
            return;
        }

        $this->columnasConocidas = $this->listarColumnas();

        foreach (self::COLUMNAS as $col) {
            $this->ensureColumn($col['name'], $col['ddl'], $col['after']);
        }

        $this->ensureIndexIdRoles();
    }

    public function down(): void
    {
        // Intencionalmente vacío: columnas pueden existir en BD legacy (alqu/neolab)
        // o haber sido creadas por esta migración; no eliminar datos.
    }

    private function ensureColumn(string $name, string $ddl, string $preferredAfter): void
    {
        if (isset($this->columnasConocidas[$name])) {
            return;
        }

        $after = $this->resolveAfter($preferredAfter);
        $afterSql = $after !== null ? " AFTER `{$after}`" : '';

        DB::statement("ALTER TABLE `usuarios` ADD COLUMN {$ddl}{$afterSql}");
        $this->columnasConocidas[$name] = true;
    }

    /**
     * Coloca la columna después del ancla si existe; si no, busca anclas previas
     * para no fallar en labs con columnas parciales.
     */
    private function resolveAfter(string $preferredAfter): ?string
    {
        $fallbackChain = [
            $preferredAfter,
            'idClientes',
            'idUsuarios',
            'permisos_ia',
            'password',
            'dni',
            'apenom',
        ];

        foreach (array_unique($fallbackChain) as $candidate) {
            if (isset($this->columnasConocidas[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /** Índice KEY `idRoles` como en lb_alqu (no-op si ya existe). */
    private function ensureIndexIdRoles(): void
    {
        if (! isset($this->columnasConocidas['idRoles'])) {
            return;
        }

        $existe = DB::selectOne(
            'SELECT 1 AS ok
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            ['usuarios', 'idRoles']
        );

        if ($existe !== null) {
            return;
        }

        DB::statement('ALTER TABLE `usuarios` ADD INDEX `idRoles` (`idRoles`)');
    }

    /** @return array<string, true> */
    private function listarColumnas(): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?',
            ['usuarios']
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->COLUMN_NAME] = true;
        }

        return $map;
    }
};
