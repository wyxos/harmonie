<?php

namespace Wyxos\Harmonie\Resource;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ResourceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/resources.php' => config_path('resources.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRouteCommand::class
            ]);
        }

        Route::middleware(config('resources.middleware'))
            ->any('/resource/{resource}/{handler}', config('resources.handler'))
            ->name('resource')
            ->where('handler', '.*');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/resources.php', 'resources'
        );
    }
}
