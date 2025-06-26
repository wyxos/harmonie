<?php

namespace Wyxos\Harmonie\Harmonie;

use Illuminate\Support\ServiceProvider;
use Wyxos\Harmonie\Harmonie\Commands\AppClear;
use Wyxos\Harmonie\Harmonie\Commands\ClearAllCache;
use Wyxos\Harmonie\Harmonie\Commands\FlushRedis;
use Wyxos\Harmonie\Harmonie\Commands\GenerateAdministrator;
use Wyxos\Harmonie\Harmonie\Commands\InstallGitHook;
use Wyxos\Harmonie\Harmonie\Commands\InstallSparkpost;
use Wyxos\Harmonie\Harmonie\Commands\ModelMakeCommand;
use Wyxos\Harmonie\Harmonie\Commands\ScoutReset;
use Wyxos\Harmonie\Harmonie\Commands\Setup;
use Wyxos\Harmonie\Harmonie\Commands\SparkpostTest;
use Wyxos\Harmonie\Harmonie\Commands\UninstallGitHook;

class HarmonieServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/harmonie.php' => config_path('harmonie.php'),
        ], 'harmonie:harmonie-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearAllCache::class,
                FlushRedis::class,
                GenerateAdministrator::class,
                ScoutReset::class,
                ModelMakeCommand::class,
                InstallGitHook::class,
                UninstallGitHook::class,
                Setup::class,
                AppClear::class,
                InstallSparkpost::class,
                SparkpostTest::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/harmonie.php', 'harmonie'
        );

        // Conditionally register ImportServiceProvider and ExportServiceProvider
        if (config('harmonie.features.import', false)) {
            $this->app->register(\Wyxos\Harmonie\Import\ImportServiceProvider::class);
        }

        if (config('harmonie.features.export', false)) {
            $this->app->register(\Wyxos\Harmonie\Export\ExportServiceProvider::class);
        }
    }
}
