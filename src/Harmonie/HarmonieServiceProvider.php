<?php

namespace Wyxos\Harmonie\Harmonie;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Wyxos\Harmonie\Harmonie\Commands\ClearAllCache;
use Wyxos\Harmonie\Harmonie\Commands\FlushRedis;
use Wyxos\Harmonie\Harmonie\Commands\GenerateAdministrator;
use Wyxos\Harmonie\Harmonie\Commands\ScoutReset;

class HarmonieServiceProvider extends ServiceProvider
{
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearAllCache::class,
                FlushRedis::class,
                GenerateAdministrator::class,
                ScoutReset::class,
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
