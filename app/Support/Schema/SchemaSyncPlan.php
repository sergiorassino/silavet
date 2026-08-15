<?php

namespace App\Support\Schema;

/**
 * Plan aditivo: crear lo que falta en el destino respecto del modelo.
 * Nunca DROP / MODIFY de objetos existentes.
 */
final class SchemaSyncPlan
{
    /**
     * @param  list<string>  $createTables
     * @param  list<array{table: string, column: string, ddl: string, after: ?string}>  $addColumns
     * @param  list<array{table: string, name: string, ddl: string}>  $addIndexes
     * @param  list<array{table: string, name: string, ddl: string}>  $addForeignKeys
     * @param  list<array{table: string, column: string, modelo: string, destino: string}>  $typeMismatches
     * @param  list<string>  $extraTables
     * @param  list<array{table: string, column: string}>  $extraColumns
     * @param  list<string>  $catalogInserts
     * @param  list<string>  $createdTableNames
     */
    public function __construct(
        public string $fromSchema,
        public string $toSchema,
        public array $createTables,
        public array $addColumns,
        public array $addIndexes,
        public array $addForeignKeys,
        public array $typeMismatches,
        public array $extraTables,
        public array $extraColumns,
        public array $catalogInserts,
        public array $createdTableNames,
    ) {}

    public function isEmpty(): bool
    {
        return $this->createTables === []
            && $this->addColumns === []
            && $this->addIndexes === []
            && $this->addForeignKeys === []
            && $this->catalogInserts === [];
    }
}
