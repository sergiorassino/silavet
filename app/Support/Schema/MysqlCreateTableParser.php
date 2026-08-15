<?php

namespace App\Support\Schema;

/**
 * Interpreta el SQL de SHOW CREATE TABLE (MySQL/MariaDB, una columna por línea).
 */
final class MysqlCreateTableParser
{
    public function parse(string $createSql): ParsedCreateTable
    {
        $sql = trim($createSql);
        $sql = rtrim($sql, ';');

        if (! preg_match('/^CREATE TABLE `([^`]+)`\s*\((.*)\)\s*(ENGINE=.+)$/is', $sql, $m)) {
            throw new \InvalidArgumentException('No se pudo interpretar SHOW CREATE TABLE.');
        }

        $table = $m[1];
        $body = $m[2];
        $options = $this->stripAutoIncrementValue(trim($m[3]));

        $columns = [];
        $indexes = [];
        $foreignKeys = [];
        $previousColumn = null;

        foreach (preg_split('/\r\n|\n|\r/', $body) as $rawLine) {
            $line = trim($rawLine);
            $line = rtrim($line, ',');
            if ($line === '') {
                continue;
            }

            $upper = strtoupper($line);

            if (str_starts_with($upper, 'CONSTRAINT ') && str_contains($upper, 'FOREIGN KEY')) {
                $foreignKeys[] = [
                    'name' => $this->constraintName($line),
                    'ddl' => $line,
                ];

                continue;
            }

            if (str_starts_with($upper, 'PRIMARY KEY')
                || str_starts_with($upper, 'UNIQUE KEY')
                || str_starts_with($upper, 'UNIQUE INDEX')
                || str_starts_with($upper, 'KEY ')
                || str_starts_with($upper, 'INDEX ')
                || str_starts_with($upper, 'FULLTEXT')
                || str_starts_with($upper, 'SPATIAL')
            ) {
                $indexes[] = [
                    'name' => $this->indexName($line),
                    'ddl' => $line,
                ];

                continue;
            }

            if (str_starts_with($line, '`')) {
                $name = $this->firstIdent($line);
                $columns[] = [
                    'name' => $name,
                    'ddl' => $line,
                    'after' => $previousColumn,
                ];
                $previousColumn = $name;
            }
        }

        return new ParsedCreateTable($table, $columns, $indexes, $foreignKeys, $options);
    }

    public function createTableIfNotExists(ParsedCreateTable $parsed): string
    {
        $lines = [];
        foreach ($parsed->columns as $col) {
            $lines[] = '  '.$col['ddl'];
        }
        foreach ($parsed->indexes as $idx) {
            $lines[] = '  '.$idx['ddl'];
        }

        $inner = implode(",\n", $lines);

        return "CREATE TABLE IF NOT EXISTS `{$parsed->table}` (\n{$inner}\n) {$parsed->options}";
    }

    /**
     * Agrega DEFAULT o relaja NOT NULL para poder ADD COLUMN en tablas con filas.
     */
    public function makeAddable(string $columnDdl): string
    {
        $upper = strtoupper($columnDdl);
        if (str_contains($upper, 'AUTO_INCREMENT') || str_contains($upper, 'DEFAULT ')) {
            return $columnDdl;
        }
        if (! str_contains($upper, 'NOT NULL')) {
            return $columnDdl;
        }
        if (preg_match('/\b(?:TINY|MEDIUM|LONG)?(?:TEXT|BLOB)\b/i', $columnDdl)) {
            return (string) preg_replace('/\sNOT NULL\b/i', ' NULL', $columnDdl, 1);
        }

        return $columnDdl.' DEFAULT '.$this->fallbackDefault($columnDdl);
    }

    public function stripAutoIncrementValue(string $options): string
    {
        return trim((string) preg_replace('/\s*AUTO_INCREMENT=\d+/i', '', $options));
    }

    private function firstIdent(string $line): string
    {
        if (preg_match('/^`([^`]+)`/', $line, $m)) {
            return $m[1];
        }

        throw new \InvalidArgumentException('Línea de columna sin identificador: '.$line);
    }

    private function indexName(string $line): string
    {
        $upper = strtoupper($line);
        if (str_starts_with($upper, 'PRIMARY KEY')) {
            return 'PRIMARY';
        }
        if (preg_match('/^(?:UNIQUE\s+)?(?:KEY|INDEX|FULLTEXT(?:\s+KEY)?|SPATIAL(?:\s+KEY)?)\s+`([^`]+)`/i', $line, $m)) {
            return $m[1];
        }

        return $line;
    }

    private function constraintName(string $line): string
    {
        if (preg_match('/^CONSTRAINT\s+`([^`]+)`/i', $line, $m)) {
            return $m[1];
        }

        return $line;
    }

    private function fallbackDefault(string $columnDdl): string
    {
        if (preg_match('/\b(DATE)\b/i', $columnDdl) && ! preg_match('/\bDATETIME\b|\bTIMESTAMP\b/i', $columnDdl)) {
            return "'1970-01-01'";
        }
        if (preg_match('/\b(DATETIME|TIMESTAMP)\b/i', $columnDdl)) {
            return "'1970-01-01 00:00:00'";
        }
        if (preg_match('/\b(INT|DECIMAL|FLOAT|DOUBLE|NUMERIC|BIT|BOOL)\b/i', $columnDdl)) {
            return '0';
        }

        return "''";
    }
}
