<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services;

use Hristijans\DatabaseMasker\Contracts\DatabaseDriverInterface;
use Hristijans\DatabaseMasker\Contracts\DatabaseMaskerInterface;
use Hristijans\DatabaseMasker\Exceptions\DatabaseDriverException;
use Hristijans\DatabaseMasker\Services\Factories\DatabaseDriverFactory;
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class DatabaseMasker implements DatabaseMaskerInterface
{
    /**
     * The application instance.
     */
    private Application $app;

    /**
     * The masker factory.
     */
    private MaskerStrategyFactory $maskerFactory;

    /**
     * The driver factory.
     */
    private DatabaseDriverFactory $driverFactory;

    /**
     * The configuration.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * The temporary SQL file path.
     */
    private string $tempSqlFile;

    /**
     * Create a new DatabaseMasker instance.
     */
    public function __construct(Application $app, MaskerStrategyFactory $maskerFactory)
    {
        $this->app = $app;
        $this->maskerFactory = $maskerFactory;
        $this->driverFactory = new DatabaseDriverFactory($maskerFactory);
        $this->config = config('database-masker');
        $this->tempSqlFile = storage_path('app/masked_database.sql');
    }

    /**
     * Create masked database dumps for all configured connections.
     *
     * @param  string|null  $outputPath  Base path for output files
     * @return array<string, array{status: string, connection: string, output_file?: string, tables_processed?: int, error?: string}>
     */
    public function createMaskedDumps(?string $outputPath = null): array
    {
        $results = [];
        $connections = $this->getConfiguredConnections();
        $outputPath = $outputPath ?? $this->config['output_path'] ?? storage_path('app');

        // Create output directory if it doesn't exist
        if (! File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        foreach ($connections as $connectionName => $connectionConfig) {
            try {
                $outputFile = $connectionConfig['output_file'] ?? "masked_database_{$connectionName}.sql";
                $fullOutputPath = rtrim($outputPath, '/').'/'.$outputFile;

                $results[$connectionName] = $this->createMaskedDumpForConnection(
                    $connectionName,
                    $connectionConfig,
                    $fullOutputPath
                );
            } catch (\Exception $e) {
                $results[$connectionName] = [
                    'status' => 'error',
                    'connection' => $connectionName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create a masked database dump for a specific connection.
     *
     * @param  array<string, mixed>  $connectionConfig
     * @return array{status: string, connection: string, output_file?: string, tables_processed?: int, error?: string}
     */
    public function createMaskedDumpForConnection(string $connectionName, array $connectionConfig, ?string $outputFile = null): array
    {
        // Get the database connection
        $connection = DB::connection($connectionName);

        // Create appropriate driver for this database
        $driver = $this->driverFactory->createDriver($connection, $connectionName);

        // Set output file
        if (! $outputFile) {
            $outputFile = storage_path('app/masked_database_'.$connectionName.'.sql');
        }

        // Start the SQL file with header
        $driver->initSqlFile($outputFile, $connectionName);

        // Get all tables excluding the ones in the exclude list
        $excludeTables = $connectionConfig['exclude_tables'] ?? [];
        $tables = $driver->getTables($excludeTables);

        $tablesProcessed = 0;
        foreach ($tables as $table) {
            // If tables are explicitly configured, only process those
            $configuredTables = array_keys($connectionConfig['tables'] ?? []);
            if (! empty($configuredTables) && ! in_array($table, $configuredTables)) {
                continue;
            }

            $this->processMaskTable(
                $driver,
                $table,
                $outputFile,
                $connectionConfig['tables'][$table] ?? null
            );
            $tablesProcessed++;
        }

        // Finalize the SQL file
        $driver->finalizeSqlFile($outputFile);

        return [
            'status' => 'success',
            'connection' => $connectionName,
            'output_file' => $outputFile,
            'tables_processed' => $tablesProcessed,
        ];
    }

    /**
     * Create a masked database dump.
     *
     * This method is kept for backward compatibility.
     */
    public function createMaskedDump(?string $outputFile = null): string
    {
        if (! $outputFile) {
            $outputFile = $this->tempSqlFile;
        }

        // Use default connection config
        $defaultConnection = config('database.default');
        $connectionConfig = [
            'tables' => $this->config['tables'] ?? [],
            'exclude_tables' => $this->config['exclude_tables'] ?? [],
        ];

        $result = $this->createMaskedDumpForConnection($defaultConnection, $connectionConfig, $outputFile);

        return $result['output_file'];
    }

    /**
     * Restore the masked database dump.
     *
     * @throws DatabaseDriverException
     */
    public function restoreMaskedDump(?string $inputFile = null, ?string $connectionName = null): bool
    {
        if (! $inputFile) {
            $inputFile = $this->tempSqlFile;
        }

        if (! file_exists($inputFile)) {
            throw new DatabaseDriverException("Masked database dump file not found: {$inputFile}");
        }

        // Set the connection
        $connectionName = $connectionName ?? config('database.default');
        $connection = DB::connection($connectionName);

        // Create appropriate driver
        $driver = $this->driverFactory->createDriver($connection, $connectionName);

        // Get connection config
        $dbConfig = config("database.connections.{$connectionName}");

        // Restore the dump
        return $driver->restoreDump($inputFile, $dbConfig);
    }

    /**
     * Process and mask a single table.
     *
     * @param  array<string, mixed>|null  $tableConfig
     */
    private function processMaskTable(
        DatabaseDriverInterface $driver,
        string $table,
        string $outputFile,
        ?array $tableConfig = null
    ): void {
        // Get table structure
        $createTableSql = $driver->getCreateTableSql($table);
        file_put_contents($outputFile, $createTableSql."\n\n", FILE_APPEND);

        // Process data in batches
        $batchSize = $this->config['batch_size'] ?? 1000;
        $connection = DB::connection();
        $totalRows = $connection->table($table)->count();

        if ($totalRows === 0) {
            return;
        }

        $batches = ceil($totalRows / $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            $offset = $i * $batchSize;
            $records = $connection->table($table)
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                continue;
            }

            $insertSql = $driver->generateInsertSql($table, $records, $tableConfig);
            file_put_contents($outputFile, $insertSql."\n\n", FILE_APPEND);
        }
    }

    /**
     * Get configured database connections.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getConfiguredConnections(): array
    {
        $connections = $this->config['connections'] ?? [];

        // If no connections are explicitly configured, use the default connection
        // with the top-level tables configuration
        if (empty($connections)) {
            $defaultConnection = config('database.default');

            return [
                $defaultConnection => [
                    'tables' => $this->config['tables'] ?? [],
                    'exclude_tables' => $this->config['exclude_tables'] ?? [],
                ],
            ];
        }

        return $connections;
    }
}
