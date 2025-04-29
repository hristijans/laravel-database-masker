<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker;

use Hristijans\DatabaseMasker\Commands\MaskDumpCommand;
use Hristijans\DatabaseMasker\Commands\MaskRestoreCommand;
use Hristijans\DatabaseMasker\Contracts\DatabaseMaskerInterface;
use Hristijans\DatabaseMasker\Services\DatabaseMasker;
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;
use Illuminate\Support\ServiceProvider;

final class DatabaseMaskerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MaskDumpCommand::class,
                MaskRestoreCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/database-masker.php' => config_path('database-masker.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/database-masker.php', 'database-masker'
        );

        $this->app->singleton(MaskerStrategyFactory::class, function ($app) {
            return new MaskerStrategyFactory;
        });

        $this->app->singleton(DatabaseMaskerInterface::class, function ($app) {
            return new DatabaseMasker(
                $app,
                $app->make(MaskerStrategyFactory::class)
            );
        });

        $this->app->alias(DatabaseMaskerInterface::class, 'database-masker');
    }
}
