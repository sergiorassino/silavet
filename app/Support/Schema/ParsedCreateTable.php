<?php

namespace App\Support\Schema;

final class ParsedCreateTable
{
    /**
     * @param  list<array{name: string, ddl: string, after: ?string}>  $columns
     * @param  list<array{name: string, ddl: string}>  $indexes
     * @param  list<array{name: string, ddl: string}>  $foreignKeys
     */
    public function __construct(
        public string $table,
        public array $columns,
        public array $indexes,
        public array $foreignKeys,
        public string $options,
    ) {}
}
