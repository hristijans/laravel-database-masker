<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Drivers;

use Hristijans\DatabaseMasker\Exceptions\DatabaseDriverException;

final class MySqlDriver extends AbstractDatabaseDriver
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

        $dbName = $this->connection->getDatabaseName();
        $rawTables = $this->connection->select('SHOW TABLES');

        $tableNameKey = "Tables_in_{$dbName}";
        foreach ($rawTables as $tableObj) {
            $tableName = $tableObj->$tableNameKey;
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
        $result = $this->connection->select("SHOW CREATE TABLE `{$table}`");
        $createTableSql = $result[0]->{'Create Table'} ?? $result[0]->{'Create View'};

        return "DROP TABLE IF EXISTS `{$table}`;\n{$createTableSql};";
    }

    /**
     * Initialize the SQL file with header.
     */
    public function initSqlFile(string $outputFile, string $connectionName): void
    {
        parent::initSqlFile($outputFile, $connectionName);

        // Add MySQL-specific settings
        $mysqlSettings = "SET FOREIGN_KEY_CHECKS=0;\n\n";
        file_put_contents($outputFile, $mysqlSettings, FILE_APPEND);
    }

    /**
     * Finalize the SQL file (add any necessary ending statements).
     */
    public function finalizeSqlFile(string $outputFile): void
    {
        file_put_contents($outputFile, "\nSET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);
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
            'mysql -h%s -u%s -p%s %s < %s',
            escapeshellarg((string) $dbConfig['host']),
            escapeshellarg((string) $dbConfig['username']),
            escapeshellarg((string) $dbConfig['password']),
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
        return '`';
    }
}
