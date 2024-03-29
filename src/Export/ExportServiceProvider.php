<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Support\ServiceProvider;
use Wyxos\Harmonie\Export\Commands\MakeExport;
use Wyxos\Harmonie\Export\Commands\Setup;

class ExportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/export.php' => config_path('export.php'),
        ], 'harmonie:export-config');

        if (config('export.load_migrations', false)) {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations/export');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeExport::class,
                Setup::class,
            ]);

            // Publish migrations
            $this->publishes([
                __DIR__.'/../../migrations/export' => database_path('migrations')
            ], 'harmonie:export-migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/export.php', 'export'
        );
    }
}
