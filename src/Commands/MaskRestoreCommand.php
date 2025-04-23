<?php

namespace Hristijans\DatabaseMasker\Commands;

use Illuminate\Console\Command;
use Hristijans\DatabaseMasker\Facades\DatabaseMasker;

class MaskRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:mask-restore 
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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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
            $inputFile = $this->option('input');
            
            // Create dump if needed
            if (!$this->option('no-dump')) {
                $this->info('Creating masked database dump...');
                $startTime = microtime(true);
                $dumpFile = DatabaseMasker::createMaskedDump($inputFile);
                $endTime = microtime(true);
                
                $this->info("Masked database dump created: {$dumpFile}");
                $this->info("Time taken: " . round($endTime - $startTime, 2) . " seconds");
                
                // Use the created dump file
                $inputFile = $dumpFile;
            } else {
                if (!$inputFile) {
                    $inputFile = storage_path('app/masked_database.sql');
                }
                
                if (!file_exists($inputFile)) {
                    $this->error("Input file not found: {$inputFile}");
                    return 1;
                }
            }
            
            $this->info('Restoring masked database...');
            
            // Confirm before overwriting the database
            if (!$this->option('force') && !$this->confirm('This will overwrite your current database. Are you sure?', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            $startTime = microtime(true);
            DatabaseMasker::restoreMaskedDump($inputFile);
            $endTime = microtime(true);
            
            $this->info("Database restored successfully with masked data!");
            $this->info("Time taken: " . round($endTime - $startTime, 2) . " seconds");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            
            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }
}