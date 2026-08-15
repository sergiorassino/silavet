<?php

namespace App\Support\Schema;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Compara un esquema modelo (NeoLab) con un laboratorio destino.
 * Solo propone altas: tablas, columnas, índices y FK faltantes.
 */
final class LegacySchemaDiffer
{
    /** Tablas de Laravel / tracking que no se copian del modelo. */
    public const SKIP_TABLES = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
        'password_resets',
        'users',
    ];

    /** Catálogos chicos: se copian filas solo si la tabla se crea de cero. */
    public const CATALOG_TABLES = [
        'permisos_ia',
        'roles',
    ];

    public function __construct(
        private readonly MysqlCreateTableParser $parser = new MysqlCreateTableParser,
    ) {}

    public function diff(string $fromSchema, string $toSchema, bool $copyCatalog = true): SchemaSyncPlan
    {
        $this->assertIdent($fromSchema);
        $this->assertIdent($toSchema);

        if ($fromSchema === $toSchema) {
            throw new RuntimeException('El esquema modelo y el destino no pueden ser el mismo.');
        }

        $fromTables = $this->baseTables($fromSchema);
        $toTables = $this->baseTables($toSchema);

        $createTables = [];
        $createdNames = [];
        $addColumns = [];
        $addIndexes = [];
        $addForeignKeys = [];
        $typeMismatches = [];
        $extraColumns = [];
        $catalogInserts = [];

        $fromSet = array_fill_keys($fromTables, true);
        $toSet = array_fill_keys($toTables, true);

        $extraTables = [];
        foreach ($toTables as $table) {
            if (! isset($fromSet[$table]) && ! in_array($table, self::SKIP_TABLES, true)) {
                $extraTables[] = $table;
            }
        }

        foreach ($fromTables as $table) {
            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }

            $parsed = $this->parser->parse($this->showCreate($fromSchema, $table));

            if (! isset($toSet[$table])) {
                $createTables[] = $this->parser->createTableIfNotExists($parsed);
                $createdNames[] = $table;

                foreach ($parsed->foreignKeys as $fk) {
                    $addForeignKeys[] = [
                        'table' => $table,
                        'name' => $fk['name'],
                        'ddl' => $fk['ddl'],
                    ];
                }

                if ($copyCatalog && in_array($table, self::CATALOG_TABLES, true)) {
                    $insert = $this->dumpCatalogInserts($fromSchema, $table);
                    if ($insert === '' && $table === 'permisos_ia') {
                        $insert = $this->permisosIaCatalogFallback();
                    }
                    if ($insert !== '') {
                        $catalogInserts[] = $insert;
                    }
                }

                continue;
            }

            $destParsed = $this->parser->parse($this->showCreate($toSchema, $table));
            $destCols = [];
            foreach ($destParsed->columns as $col) {
                $destCols[$col['name']] = $col['ddl'];
            }

            $addedThisTable = [];
            foreach ($parsed->columns as $col) {
                if (! isset($destCols[$col['name']])) {
                    $after = $col['after'];
                    $afterOk = $after !== null
                        && (isset($destCols[$after]) || isset($addedThisTable[$after]));
                    $ddl = $this->parser->makeAddable($col['ddl']);
                    if ($afterOk) {
                        $ddl .= ' AFTER `'.$after.'`';
                    }
                    $addColumns[] = [
                        'table' => $table,
                        'column' => $col['name'],
                        'ddl' => $ddl,
                        'after' => $afterOk ? $after : null,
                    ];
                    $addedThisTable[$col['name']] = true;

                    continue;
                }

                if ($this->normalizeCol($col['ddl']) !== $this->normalizeCol($destCols[$col['name']])) {
                    $typeMismatches[] = [
                        'table' => $table,
                        'column' => $col['name'],
                        'modelo' => $col['ddl'],
                        'destino' => $destCols[$col['name']],
                    ];
                }
            }

            foreach ($destParsed->columns as $col) {
                $known = false;
                foreach ($parsed->columns as $src) {
                    if ($src['name'] === $col['name']) {
                        $known = true;
                        break;
                    }
                }
                if (! $known) {
                    $extraColumns[] = ['table' => $table, 'column' => $col['name']];
                }
            }

            $destIdx = [];
            foreach ($destParsed->indexes as $idx) {
                $destIdx[strtolower($idx['name'])] = true;
            }
            foreach ($parsed->indexes as $idx) {
                if (! isset($destIdx[strtolower($idx['name'])])) {
                    $addIndexes[] = [
                        'table' => $table,
                        'name' => $idx['name'],
                        'ddl' => $idx['ddl'],
                    ];
                }
            }

            $destFk = [];
            foreach ($destParsed->foreignKeys as $fk) {
                $destFk[strtolower($fk['name'])] = true;
            }
            foreach ($parsed->foreignKeys as $fk) {
                if (! isset($destFk[strtolower($fk['name'])])) {
                    $addForeignKeys[] = [
                        'table' => $table,
                        'name' => $fk['name'],
                        'ddl' => $fk['ddl'],
                    ];
                }
            }
        }

        return new SchemaSyncPlan(
            fromSchema: $fromSchema,
            toSchema: $toSchema,
            createTables: $createTables,
            addColumns: $addColumns,
            addIndexes: $addIndexes,
            addForeignKeys: $addForeignKeys,
            typeMismatches: $typeMismatches,
            extraTables: $extraTables,
            extraColumns: $extraColumns,
            catalogInserts: $catalogInserts,
            createdTableNames: $createdNames,
        );
    }

    public function renderSql(SchemaSyncPlan $plan): string
    {
        $lines = [];
        $lines[] = '-- SILAVET — sincronización aditiva de esquema';
        $lines[] = '-- Generado por: php artisan lb:schema-sync';
        $lines[] = '-- Modelo : '.$plan->fromSchema;
        $lines[] = '-- Destino: '.$plan->toSchema;
        $lines[] = '-- Fecha  : '.now()->format('Y-m-d H:i:s');
        $lines[] = '--';
        $lines[] = '-- ADITIVO: no elimina tablas/columnas ni modifica tipos existentes.';
        $lines[] = '-- Ejecutar sobre la BD destino (USE `'.$plan->toSchema.'`).';
        $lines[] = '-- Después: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force';
        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = '';

        if ($plan->createTables !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Tablas faltantes';
            $lines[] = '-- ---------------------------------------------------------------------------';
            foreach ($plan->createTables as $ddl) {
                $lines[] = $ddl.';';
                $lines[] = '';
            }
        }

        if ($plan->addColumns !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Columnas faltantes';
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '';
            foreach ($plan->addColumns as $col) {
                $lines[] = $this->idempotentAddColumn($col['table'], $col['column'], $col['ddl']);
            }
        }

        if ($plan->addIndexes !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Índices faltantes';
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '';
            foreach ($plan->addIndexes as $idx) {
                if (strtoupper($idx['name']) === 'PRIMARY') {
                    $lines[] = '-- Omitido PRIMARY KEY en `'.$idx['table'].'` (la tabla ya existe).';
                    $lines[] = '';

                    continue;
                }
                $lines[] = $this->idempotentIndex($idx['table'], $idx['name'], $idx['ddl']);
            }
        }

        if ($plan->addForeignKeys !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Claves foráneas faltantes';
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '';
            foreach ($plan->addForeignKeys as $fk) {
                $lines[] = $this->idempotentForeignKey($fk['table'], $fk['name'], $fk['ddl']);
            }
        }

        if ($plan->catalogInserts !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Catálogos (solo tablas creadas vacías)';
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '';
            foreach ($plan->catalogInserts as $insert) {
                $lines[] = $insert;
                $lines[] = '';
            }
        }

        if ($plan->typeMismatches !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Diferencias de tipo (NO se modifican; revisar a mano si hace falta)';
            $lines[] = '-- ---------------------------------------------------------------------------';
            foreach ($plan->typeMismatches as $diff) {
                $lines[] = '-- '.$diff['table'].'.'.$diff['column'];
                $lines[] = '--   modelo : '.$diff['modelo'];
                $lines[] = '--   destino: '.$diff['destino'];
            }
            $lines[] = '';
        }

        if ($plan->extraTables !== [] || $plan->extraColumns !== []) {
            $lines[] = '-- ---------------------------------------------------------------------------';
            $lines[] = '-- Solo en destino (no se eliminan)';
            $lines[] = '-- ---------------------------------------------------------------------------';
            foreach ($plan->extraTables as $table) {
                $lines[] = '-- tabla extra: '.$table;
            }
            foreach ($plan->extraColumns as $col) {
                $lines[] = '-- columna extra: '.$col['table'].'.'.$col['column'];
            }
            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function schemaExists(string $schema): bool
    {
        $this->assertIdent($schema);
        $row = DB::selectOne(
            'SELECT SCHEMA_NAME AS n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$schema]
        );

        return $row !== null;
    }

    /** @return list<string> */
    public function baseTables(string $schema): array
    {
        $this->assertIdent($schema);
        $rows = DB::select(
            "SELECT TABLE_NAME AS n
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME",
            [$schema]
        );

        return array_map(fn ($row) => (string) $row->n, $rows);
    }

    public function apply(SchemaSyncPlan $plan): void
    {
        $this->assertIdent($plan->toSchema);
        DB::statement('USE `'.$plan->toSchema.'`');
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($plan->createTables as $ddl) {
            DB::statement($ddl);
        }

        $toCols = $this->columnMap($plan->toSchema);

        foreach ($plan->addColumns as $col) {
            $key = $col['table'].'.'.$col['column'];
            if (isset($toCols[$key])) {
                continue;
            }
            $ddl = $col['ddl'];
            if ($col['after'] !== null && ! isset($toCols[$col['table'].'.'.$col['after']])) {
                $ddl = (string) preg_replace('/\sAFTER\s+`[^`]+`\s*$/i', '', $ddl);
            }
            DB::statement('ALTER TABLE `'.$col['table'].'` ADD COLUMN '.$ddl);
            $toCols[$key] = true;
        }

        $toIdx = $this->indexMap($plan->toSchema);
        foreach ($plan->addIndexes as $idx) {
            if (strtoupper($idx['name']) === 'PRIMARY') {
                continue;
            }
            $key = strtolower($idx['table'].'.'.$idx['name']);
            if (isset($toIdx[$key])) {
                continue;
            }
            DB::statement('ALTER TABLE `'.$idx['table'].'` ADD '.$idx['ddl']);
        }

        $toFk = $this->foreignKeyMap($plan->toSchema);
        foreach ($plan->addForeignKeys as $fk) {
            $key = strtolower($fk['table'].'.'.$fk['name']);
            if (isset($toFk[$key])) {
                continue;
            }
            DB::statement('ALTER TABLE `'.$fk['table'].'` ADD '.$fk['ddl']);
        }

        foreach ($plan->catalogInserts as $insert) {
            foreach ($this->splitStatements($insert) as $stmt) {
                DB::statement($stmt);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function assertIdent(string $name): void
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Nombre de esquema/tabla inválido: '.$name);
        }
    }

    private function showCreate(string $schema, string $table): string
    {
        $this->assertIdent($schema);
        $this->assertIdent($table);
        $rows = DB::select("SHOW CREATE TABLE `{$schema}`.`{$table}`");
        if ($rows === []) {
            throw new RuntimeException("No se obtuvo CREATE TABLE de {$schema}.{$table}");
        }
        $row = (array) $rows[0];
        foreach ($row as $key => $value) {
            if (strcasecmp((string) $key, 'Create Table') === 0) {
                return (string) $value;
            }
        }

        throw new RuntimeException("SHOW CREATE TABLE sin columna Create Table ({$schema}.{$table})");
    }

    private function dumpCatalogInserts(string $schema, string $table): string
    {
        $this->assertIdent($schema);
        $this->assertIdent($table);
        $rows = DB::select("SELECT * FROM `{$schema}`.`{$table}`");
        if ($rows === []) {
            return '';
        }

        $columns = null;
        $chunks = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            $columns ??= array_keys($arr);
            $values = [];
            foreach ($columns as $col) {
                $values[] = $this->sqlLiteral($arr[$col] ?? null);
            }
            $chunks[] = '('.implode(', ', $values).')';
        }

        $colList = implode(', ', array_map(fn (string $c) => '`'.$c.'`', $columns));

        return 'INSERT IGNORE INTO `'.$table.'` ('.$colList.") VALUES\n".implode(",\n", $chunks).';';
    }

    /** Si el modelo no tiene filas en permisos_ia, usa el catálogo del repo. */
    private function permisosIaCatalogFallback(): string
    {
        return <<<'SQL'
INSERT IGNORE INTO `permisos_ia` (`orden`, `tema`, `descripcion`) VALUES
(0, 'Clientes', 'ABM clientes veterinarios'),
(1, 'Especies', 'ABM especies y razas'),
(2, 'Determinaciones', 'ABM tipos de determinación'),
(3, 'Protocolos', 'Recepción y gestión de protocolos'),
(4, 'Resultados', 'Carga de resultados'),
(5, 'Informes', 'Emisión y envío de informes'),
(6, 'Facturación', 'Comprobantes y cobranza'),
(7, 'Reactivos', 'Stock de reactivos'),
(8, 'Parámetros', 'Configuración del laboratorio'),
(9, 'Usuarios', 'ABM usuarios y roles'),
(10, 'Listados estadísticos', 'Estimación de costos y listados estadísticos');
SQL;
    }

    private function sqlLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $pdo = DB::connection()->getPdo();

        return $pdo->quote((string) $value);
    }

    private function normalizeCol(string $ddl): string
    {
        $ddl = preg_replace('/\s+/', ' ', trim($ddl)) ?? $ddl;

        return strtolower($ddl);
    }

    private function quoteSqlString(string $value): string
    {
        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }

    private function idempotentAddColumn(string $table, string $column, string $definition): string
    {
        $this->assertIdent($table);
        $this->assertIdent($column);
        $escaped = $this->quoteSqlString('ALTER TABLE `'.$table.'` ADD COLUMN '.$definition);

        return <<<SQL
SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND COLUMN_NAME = '{$column}'
        ),
        {$escaped},
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SQL;
    }

    private function idempotentIndex(string $table, string $name, string $ddl): string
    {
        $this->assertIdent($table);
        $this->assertIdent($name);
        $escaped = $this->quoteSqlString('ALTER TABLE `'.$table.'` ADD '.$ddl);

        return <<<SQL
SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND INDEX_NAME = '{$name}'
        ),
        {$escaped},
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SQL;
    }

    private function idempotentForeignKey(string $table, string $name, string $ddl): string
    {
        $this->assertIdent($table);
        $this->assertIdent($name);
        $escaped = $this->quoteSqlString('ALTER TABLE `'.$table.'` ADD '.$ddl);

        return <<<SQL
SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND CONSTRAINT_NAME = '{$name}'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        {$escaped},
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SQL;
    }

    /** @return array<string, true> */
    private function columnMap(string $schema): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?',
            [$schema]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row->t.'.'.$row->c] = true;
        }

        return $map;
    }

    /** @return array<string, true> */
    private function indexMap(string $schema): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME AS t, INDEX_NAME AS n
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?',
            [$schema]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[strtolower($row->t.'.'.$row->n)] = true;
        }

        return $map;
    }

    /** @return array<string, true> */
    private function foreignKeyMap(string $schema): array
    {
        $rows = DB::select(
            "SELECT TABLE_NAME AS t, CONSTRAINT_NAME AS n
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$schema]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[strtolower($row->t.'.'.$row->n)] = true;
        }

        return $map;
    }

    /** @return list<string> */
    private function splitStatements(string $sql): array
    {
        $parts = array_filter(array_map('trim', explode(';', $sql)));

        return array_values(array_map(fn (string $s) => rtrim($s, ';'), $parts));
    }
}
