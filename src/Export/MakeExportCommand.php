<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeExportCommand extends Command
{
    protected $signature = 'make:export {name}';

    protected $description = 'Create a new export class';

    public function handle()
    {
        $name = $this->argument('name');
        $baseClass = config('exports.base');

        $stub = File::get(__DIR__ . '/../../stubs/export.stub');

        $stub = str_replace('{{className}}', $name, $stub);
        $stub = str_replace('{{baseClass}}', class_basename($baseClass), $stub);

        // Ensure Exports directory exists
        File::ensureDirectoryExists(app_path('Exports'));

        File::put(app_path("/Exports/{$name}.php"), $stub);

        $this->info("Export class {$name} created successfully.");
    }
}