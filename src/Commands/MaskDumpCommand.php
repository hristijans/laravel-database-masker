<?php

namespace Hristijans\DatabaseMasker\Commands;

use Illuminate\Console\Command;
use Hristijans\DatabaseMasker\Facades\DatabaseMasker;

class MaskDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:mask-dump 
                            {--output= : Output file path (default: storage/app/masked_database.sql)}
                            {--config= : Custom config file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a masked database dump with sensitive data obfuscated';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating masked database dump...');
        
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
            $outputPath = DatabaseMasker::createMaskedDump($this->option('output'));
            $endTime = microtime(true);
            
            $this->info("Masked database dump created successfully!");
            $this->info("Output file: {$outputPath}");
            $this->info("Time taken: " . round($endTime - $startTime, 2) . " seconds");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error creating masked database dump: " . $e->getMessage());
            
            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }
}