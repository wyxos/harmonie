<?php

namespace Wyxos\Harmonie\Import;

use Illuminate\Support\ServiceProvider;
use Wyxos\Harmonie\Import\Commands\MakeImport;
use Wyxos\Harmonie\Import\Commands\Setup;

class ImportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/import.php' => config_path('import.php'),
        ], 'harmonie:import-config');

        if (config('import.load_migrations', false)) {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations/import');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Setup::class,
                MakeImport::class,
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
