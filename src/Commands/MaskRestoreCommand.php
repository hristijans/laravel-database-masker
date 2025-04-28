<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Commands;

use Hristijans\DatabaseMasker\Contracts\DatabaseMaskerInterface;
use Illuminate\Console\Command;

final class MaskRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:mask-restore
                            {--connection= : Database connection to restore to}
                            {--input= : Input file path (default: storage/app/masked_database.sql)}
                            {--no-dump : Skip creating dump and use existing file}
                            {--config= : Custom config file path}
                            {--force : Force restore without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and restore a masked database dump in one step';

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
            if (! file_exists($configPath)) {
                $this->error("Config file not found: {$configPath}");

                return 1;
            }

            $customConfig = include $configPath;
            config(['database-masker' => $customConfig]);
        }

        try {
            $connectionName = $this->option('connection') ?? config('database.default');
            $inputFile = $this->option('input');

            // Create dump if needed
            if (! $this->option('no-dump')) {
                $this->info("Creating masked database dump for connection: {$connectionName}...");

                // Get connection config
                $connections = config('database-masker.connections', []);
                $connectionConfig = $connections[$connectionName] ?? null;

                if (! $connectionConfig && empty($connections)) {
                    // Use default top-level config if no connections defined
                    $connectionConfig = [
                        'tables' => config('database-masker.tables', []),
                        'exclude_tables' => config('database-masker.exclude_tables', []),
                    ];
                }

                if (! $connectionConfig) {
                    $this->error("Connection '{$connectionName}' not found in configuration.");

                    return 1;
                }

                $startTime = microtime(true);

                $result = $this->databaseMasker->createMaskedDumpForConnection(
                    $connectionName,
                    $connectionConfig,
                    $inputFile
                );

                $endTime = microtime(true);

                if ($result['status'] === 'success') {
                    $this->info("Masked database dump created: {$result['output_file']}");
                    $this->info('Time taken: '.round($endTime - $startTime, 2).' seconds');

                    // Use the created dump file
                    $inputFile = $result['output_file'];
                } else {
                    $this->error("Failed to create masked database dump: {$result['error']}");

                    return 1;
                }
            } else {
                if (! $inputFile) {
                    // Try to guess the input file based on connection name
                    $inputFile = storage_path("app/masked_database_{$connectionName}.sql");

                    if (! file_exists($inputFile)) {
                        // Fall back to legacy path
                        $inputFile = storage_path('app/masked_database.sql');
                    }
                }

                if (! file_exists($inputFile)) {
                    $this->error("Input file not found: {$inputFile}");

                    return 1;
                }
            }

            $this->info("Restoring masked database to connection: {$connectionName}...");

            // Confirm before overwriting the database
            if (! $this->option('force') && ! $this->confirm("This will overwrite the database for connection '{$connectionName}'. Are you sure?", true)) {
                $this->info('Operation cancelled.');

                return 0;
            }

            $startTime = microtime(true);
            $this->databaseMasker->restoreMaskedDump($inputFile, $connectionName);
            $endTime = microtime(true);

            $this->info('Database restored successfully with masked data!');
            $this->info("Connection: {$connectionName}");
            $this->info('Time taken: '.round($endTime - $startTime, 2).' seconds');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}
