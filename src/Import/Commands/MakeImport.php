<?php

namespace Wyxos\Harmonie\Import\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeImport extends Command
{
    protected $signature = 'make:import {name}';

    protected $description = 'Create a new import class';

    public function handle(): void
    {
        $name = $this->argument('name');
        $baseClass = config('import.base');

        $stub = File::get(__DIR__.'/../../../stubs/Import.stub');

        $stub = str_replace('{{className}}', $name, $stub);
        $stub = str_replace('{{baseClass}}', class_basename($baseClass), $stub);
        $stub = str_replace('{{baseClassFull}}', $baseClass, $stub);

        File::ensureDirectoryExists(app_path('Imports'));

        File::put(app_path("/Imports/{$name}.php"), $stub);

        $this->info("Import class {$name} created successfully.");
    }
}