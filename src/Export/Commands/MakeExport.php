<?php

namespace Wyxos\Harmonie\Export\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeExport extends Command
{
    protected $signature = 'make:export {name}';

    protected $description = 'Create a new export class';

    public function handle(): void
    {
        $name = $this->argument('name');
        $baseClass = config('export.base');

        $stub = File::get(__DIR__.'/../../stubs/Export.stub');

        $stub = str_replace('{{className}}', $name, $stub);
        $stub = str_replace('{{baseClass}}', class_basename($baseClass), $stub);
        $stub = str_replace('{{baseClassFull}}', $baseClass, $stub);

        File::ensureDirectoryExists(app_path('Exports'));

        File::put(app_path("/Exports/{$name}.php"), $stub);

        $this->info("Export class {$name} created successfully.");
    }
}