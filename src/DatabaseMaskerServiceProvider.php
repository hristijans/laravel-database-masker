<?php

namespace Hristijans\DatabaseMasker;

use Illuminate\Support\ServiceProvider;
use Hristijans\DatabaseMasker\Commands\MaskDumpCommand;
use Hristijans\DatabaseMasker\Commands\MaskRestoreCommand;

class DatabaseMaskerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MaskDumpCommand::class,
                MaskRestoreCommand::class,
            ]);
            
            $this->publishes([
                __DIR__ . '/../config/database-masker.php' => config_path('database-masker.php'),
            ], 'config');
        }
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/database-masker.php', 'database-masker'
        );
        
        $this->app->singleton('database-masker', function ($app) {
            return new DatabaseMasker($app);
        });
    }
}