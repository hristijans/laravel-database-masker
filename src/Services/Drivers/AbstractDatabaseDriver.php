<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Drivers;

use Hristijans\DatabaseMasker\Contracts\DatabaseDriverInterface;
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

abstract class AbstractDatabaseDriver implements DatabaseDriverInterface
{
    /**
     * The database connection.
     */
    protected Connection $connection;

    /**
     * The connection name.
     */
    protected string $connectionName;

    /**
     * The masker factory.
     */
    protected MaskerStrategyFactory $maskerFactory;

    /**
     * Create a new instance.
     */
    public function __construct(MaskerStrategyFactory $maskerFactory)
    {
        $this->maskerFactory = $maskerFactory;
    }

    /**
     * Initialize the driver with a database connection.
     */
    public function initialize(Connection $connection, string $connectionName): void
    {
        $this->connection = $connection;
        $this->connectionName = $connectionName;
    }

    /**
     * Format value for SQL insertion.
     */
    public function formatSqlValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_numeric($value) => (string) $value,
            default => "'".addslashes((string) $value)."'"
        };
    }

    /**
     * Generate INSERT SQL statements with masked data.
     *
     * @param  array<string, mixed>|null  $tableConfig
     */
    public function generateInsertSql(string $table, Collection $records, ?array $tableConfig = null): string
    {
        $schemaBuilder = Schema::connection($this->connectionName);
        $columns = $schemaBuilder->getColumnListing($table);
        $maskColumns = $tableConfig['columns'] ?? [];

        // Get appropriate quote character for identifiers
        $quoteChar = $this->getIdentifierQuoteCharacter();

        $insertSql = "INSERT INTO {$quoteChar}{$table}{$quoteChar} ({$quoteChar}".
            implode("{$quoteChar}, {$quoteChar}", $columns)."{$quoteChar}) VALUES\n";
        $valuesSql = [];

        foreach ($records as $record) {
            $values = [];

            foreach ($columns as $column) {
                $value = $record->$column;

                // Determine if this column should be masked
                if (isset($maskColumns[$column])) {
                    $config = $maskColumns[$column];
                    $type = $config['type'] ?? 'text';

                    $masker = $this->maskerFactory->createMasker($type);
                    $value = $masker->mask($value, $config);
                }

                // Format value for SQL
                $values[] = $this->formatSqlValue($value);
            }

            $valuesSql[] = '('.implode(', ', $values).')';
        }

        return $insertSql.implode(",\n", $valuesSql).';';
    }

    /**
     * Initialize the SQL file with header.
     */
    public function initSqlFile(string $outputFile, string $connectionName): void
    {
        $connectionInfo = $connectionName ? " for connection '{$connectionName}'" : '';
        $header = "-- Database Masked Dump{$connectionInfo}\n";
        $header .= '-- Generated on: '.date('Y-m-d H:i:s')."\n";
        $header .= "-- By: Laravel Database Masker\n\n";

        file_put_contents($outputFile, $header);
    }

    /**
     * Finalize the SQL file (add any necessary ending statements).
     */
    public function finalizeSqlFile(string $outputFile): void
    {
        // Default implementation does nothing, but can be overridden by driver-specific implementations
    }

    /**
     * Get the appropriate identifier quote character for the current database driver.
     */
    abstract protected function getIdentifierQuoteCharacter(): string;
}
