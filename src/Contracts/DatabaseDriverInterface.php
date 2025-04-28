<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Contracts;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

interface DatabaseDriverInterface
{
    /**
     * Initialize the driver with a database connection.
     */
    public function initialize(Connection $connection, string $connectionName): void;

    /**
     * Get all database tables.
     *
     * @param  array<int, string>  $excludeTables
     * @return array<int, string>
     */
    public function getTables(array $excludeTables = []): array;

    /**
     * Get CREATE TABLE statement for a table.
     */
    public function getCreateTableSql(string $table): string;

    /**
     * Initialize the SQL file with appropriate header.
     */
    public function initSqlFile(string $outputFile, string $connectionName): void;

    /**
     * Format value for SQL insertion.
     */
    public function formatSqlValue(mixed $value): string;

    /**
     * Generate INSERT SQL statements with masked data.
     *
     * @param  array<string, mixed>|null  $tableConfig
     */
    public function generateInsertSql(string $table, Collection $records, ?array $tableConfig = null): string;

    /**
     * Restore a SQL dump to the database.
     *
     * @param  array<string, mixed>  $dbConfig
     */
    public function restoreDump(string $inputFile, array $dbConfig): bool;

    /**
     * Finalize the SQL file (add any necessary ending statements).
     */
    public function finalizeSqlFile(string $outputFile): void;
}
