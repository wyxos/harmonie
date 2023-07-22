<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Support\ServiceProvider;
use Wyxos\Harmonie\Resource\MakeRouteCommand;

class ExportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/export.php' => config_path('export.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRouteCommand::class
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations/export');

        if ($this->app->runningInConsole()) {
            // Publish migrations
            $this->publishes([
                __DIR__.'/../../migrations/export' => database_path('migrations')
            ], 'migrations');

            $this->commands([
                MakeRouteCommand::class
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/export.php', 'export'
        );
    }
}