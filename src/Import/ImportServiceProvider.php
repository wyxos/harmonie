<?php

namespace Wyxos\Harmonie\Import;

use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/import.php' => config_path('import.php'),
        ], 'harmonie:import-config');

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations/import');

        if ($this->app->runningInConsole()) {
            $this->commands([
            ]);

            // Publish migrations
            $this->publishes([
                __DIR__.'/../../migrations/import' => database_path('migrations')
            ], 'harmonie:import-migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/import.php', 'import'
        );
    }
}