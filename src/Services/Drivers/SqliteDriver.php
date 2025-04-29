<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Drivers;

use Hristijans\DatabaseMasker\Exceptions\DatabaseDriverException;

final class SqliteDriver extends AbstractDatabaseDriver
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

        $rawTables = $this->connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
        );

        foreach ($rawTables as $tableObj) {
            $tableName = $tableObj->name;
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
        $createTable = $this->connection->select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$table]);

        if (empty($createTable)) {
            throw new DatabaseDriverException("Table {$table} not found in SQLite database");
        }

        $createTableSql = $createTable[0]->sql;

        return "DROP TABLE IF EXISTS \"{$table}\";\n{$createTableSql};";
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
        $sqliteFile = $dbConfig['database'];

        if ($sqliteFile === ':memory:') {
            throw new DatabaseDriverException('Cannot restore to an in-memory SQLite database');
        }

        // Backup the original database
        if (file_exists($sqliteFile)) {
            copy($sqliteFile, $sqliteFile.'.backup');
        }

        // Create empty file
        file_put_contents($sqliteFile, '');

        // Import SQL
        $command = sprintf(
            'sqlite3 %s < %s',
            escapeshellarg($sqliteFile),
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
