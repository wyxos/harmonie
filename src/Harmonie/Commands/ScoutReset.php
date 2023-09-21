<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ScoutReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:reset {models* : The Eloquent models to flush and reimport}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush and reimport the given models into the search index';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $models = $this->argument('models');

        // If no models are supplied as arguments, get them from the package config
        if (empty($models)) {
            $models = config('harmonie.scout', []);
        }

        if (empty($models)) {
            $this->error("No models specified for the operation.");
            return Command::FAILURE;
        }

        collect($models)
            ->each(fn($model) => Artisan::call('scout:flush', ['model' => $model]))
            ->each(fn($model) => Artisan::call('scout:import', ['model' => $model]));

        return Command::SUCCESS;
    }
}
