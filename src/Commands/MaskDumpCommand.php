<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Commands;

use Hristijans\DatabaseMasker\Contracts\DatabaseMaskerInterface;
use Illuminate\Console\Command;

final class MaskDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:mask-dump
                            {--connection= : Specific connection to process (default: all configured)}
                            {--output-path= : Output directory path for dump files}
                            {--output= : Output file path (for single connection only)}
                            {--config= : Custom config file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create masked database dumps with sensitive data obfuscated';

    /**
     * The database masker service.
     */
    private DatabaseMaskerInterface $databaseMasker;

    /**
     * Create a new command instance.
     */
    public function __construct(DatabaseMaskerInterface $databaseMasker)
    {
        parent::__construct();
        $this->databaseMasker = $databaseMasker;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Load custom config if provided
        if ($configPath = $this->option('config')) {
            if (!file_exists($configPath)) {
                $this->error("Config file not found: {$configPath}");
                return 1;
            }

            $customConfig = include $configPath;
            config(['database-masker' => $customConfig]);
        }

        try {
            $startTime = microtime(true);
            $specificConnection = $this->option('connection');

            if ($specificConnection) {
                // Process a single connection
                $this->info("Creating masked database dump for connection: {$specificConnection}...");

                // Get connection config
                $connections = config('database-masker.connections', []);
                $connectionConfig = $connections[$specificConnection] ?? null;

                if (!$connectionConfig && empty($connections)) {
                    // Use default top-level config if no connections defined
                    $connectionConfig = [
                        'tables' => config('database-masker.tables', []),
                        'exclude_tables' => config('database-masker.exclude_tables', []),
                    ];
                }

                if (!$connectionConfig) {
                    $this->error("Connection '{$specificConnection}' not found in configuration.");
                    return 1;
                }

                $outputFile = $this->option('output');

                $result = $this->databaseMasker->createMaskedDumpForConnection(
                    $specificConnection,
                    $connectionConfig,
                    $outputFile
                );

                $this->displayResult($result);
            } else {
                // Process all configured connections
                $this->info('Creating masked database dumps for all configured connections...');

                $outputPath = $outputPath ?? $this->config['output_path'] ?? $this->laravel->storagePath('app');
                $results = $this->databaseMasker->createMaskedDumps($outputPath);

                foreach ($results as $connectionName => $result) {
                    $this->displayResult($result);
                }
            }

            $endTime = microtime(true);
            $this->info("Total time taken: " . round($endTime - $startTime, 2) . " seconds");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error creating masked database dump: " . $e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Display the result of a database dump operation.
     *
     * @param array{status: string, connection: string, output_file?: string, tables_processed?: int, error?: string} $result
     */
    private function displayResult(array $result): void
    {
        $connection = $result['connection'] ?? 'default';

        if ($result['status'] === 'success') {
            $this->info("Connection '{$connection}': Masked database dump created successfully!");
            $this->info("Output file: {$result['output_file']}");
            $this->info("Tables processed: {$result['tables_processed']}");
        } else {
            $this->error("Connection '{$connection}': Failed to create masked database dump.");
            if (isset($result['error'])) {
                $this->error("Error: {$result['error']}");
            }
        }

        $this->line('');
    }
}
