<?php

namespace Wyxos\Harmonie\Harmonie;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Wyxos\Harmonie\Harmonie\Commands\FlushRedis;
use Wyxos\Harmonie\Harmonie\Commands\GenerateAdministrator;

class HarmonieServiceProvider extends ServiceProvider
{
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->commands([
                FlushRedis::class,
                GenerateAdministrator::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/harmonie.php', 'harmonie'
        );
    }
}
