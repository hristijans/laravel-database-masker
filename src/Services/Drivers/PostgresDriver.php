<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Drivers;

use Hristijans\DatabaseMasker\Exceptions\DatabaseDriverException;

final class PostgresDriver extends AbstractDatabaseDriver
{
    /**
     * Get all database tables.
     *
     * @param  array<int, string>  $excludeTables
     * @return array<int, string>
     */
    public function getTables(array $excludeTables = []): array
    {
        $tables = [];

        $schema = 'public'; // Default schema, could be made configurable
        $rawTables = $this->connection->select(
            'SELECT tablename FROM pg_tables WHERE schemaname = ?',
            [$schema]
        );

        foreach ($rawTables as $tableObj) {
            $tableName = $tableObj->tablename;
            if (! in_array($tableName, $excludeTables)) {
                $tables[] = $tableName;
            }
        }

        return $tables;
    }

    /**
     * Get CREATE TABLE statement for a table.
     */
    public function getCreateTableSql(string $table): string
    {
        $schema = 'public'; // Default schema

        try {
            // Get table definition
            $result = $this->connection->select(
                "SELECT pg_get_tabledef('{$schema}.{$table}'::regclass::oid) as definition"
            );

            if (empty($result)) {
                throw new DatabaseDriverException("Could not get table definition for {$table}");
            }

            $tableDefinition = $result[0]->definition;

            return "DROP TABLE IF EXISTS \"{$table}\" CASCADE;\n{$tableDefinition};";
        } catch (\Exception $e) {
            // Fallback method if pg_get_tabledef is not available
            // Get column definitions
            $columns = $this->connection->select(
                'SELECT column_name, data_type, character_maximum_length, column_default, is_nullable
                FROM information_schema.columns
                WHERE table_schema = ? AND table_name = ?
                ORDER BY ordinal_position',
                [$schema, $table]
            );

            if (empty($columns)) {
                throw new DatabaseDriverException("Table {$table} not found in PostgreSQL database");
            }

            $createTable = "CREATE TABLE \"{$table}\" (\n";
            $columnDefs = [];

            foreach ($columns as $column) {
                $type = $column->data_type;
                if ($type === 'character varying' && $column->character_maximum_length !== null) {
                    $type = "varchar({$column->character_maximum_length})";
                }

                $nullable = $column->is_nullable === 'YES' ? '' : ' NOT NULL';
                $default = $column->column_default !== null ? " DEFAULT {$column->column_default}" : '';

                $columnDefs[] = "  \"{$column->column_name}\" {$type}{$nullable}{$default}";
            }

            $createTable .= implode(",\n", $columnDefs);
            $createTable .= "\n);";

            return "DROP TABLE IF EXISTS \"{$table}\" CASCADE;\n{$createTable}";
        }
    }

    /**
     * Initialize the SQL file with header.
     */
    public function initSqlFile(string $outputFile, string $connectionName): void
    {
        parent::initSqlFile($outputFile, $connectionName);

        // Add PostgreSQL-specific settings
        $pgSettings = "SET session_replication_role = 'replica';\n\n";
        file_put_contents($outputFile, $pgSettings, FILE_APPEND);
    }

    /**
     * Finalize the SQL file (add any necessary ending statements).
     */
    public function finalizeSqlFile(string $outputFile): void
    {
        file_put_contents($outputFile, "\nSET session_replication_role = 'origin';\n", FILE_APPEND);
    }

    /**
     * Restore a SQL dump to the database.
     *
     * @param  array<string, mixed>  $dbConfig
     *
     * @throws DatabaseDriverException
     */
    public function restoreDump(string $inputFile, array $dbConfig): bool
    {
        $command = sprintf(
            'PGPASSWORD=%s psql -h %s -U %s -d %s -f %s',
            escapeshellarg((string) $dbConfig['password']),
            escapeshellarg((string) $dbConfig['host']),
            escapeshellarg((string) $dbConfig['username']),
            escapeshellarg((string) $dbConfig['database']),
            escapeshellarg($inputFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new DatabaseDriverException('Failed to restore database: '.implode("\n", $output));
        }

        return true;
    }

    /**
     * Get the appropriate identifier quote character for the current database driver.
     */
    protected function getIdentifierQuoteCharacter(): string
    {
        return '"';
    }
}
