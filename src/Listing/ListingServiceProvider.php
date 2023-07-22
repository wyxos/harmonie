<?php

namespace Wyxos\Harmonie\Listing;

use Illuminate\Support\ServiceProvider;

class ListingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/listing.php' => config_path('listing.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeListingCommand::class
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/listing.php', 'listing'
        );
    }
}
