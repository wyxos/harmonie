<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Support\Facades\File;

class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('pivot')) {
            return __DIR__ . '/../../../stubs/model.pivot.stub';
        }

        if ($this->option('morph-pivot')) {
            return __DIR__ . '/../../../stubs/model.morph-pivot.stub';
        }

        // Use your own stub file
        return __DIR__ . '/../../../stubs/model.stub';
    }

    public function handle()
    {
        // Check if App\Model.php exists
        $appModelPath = app_path('Model.php');

        if (!File::exists($appModelPath)) {
            // Create App\Model.php
            $stubPath = __DIR__ . '/../../../stubs/model.base.stub';
            $content = File::get($stubPath);

            // Replace the namespace placeholder with the application's root namespace
// Replace the namespace placeholder with the application's root namespace
            $namespace = rtrim($this->laravel->getNamespace(), '\\'); // Remove trailing backslashes
            $content = str_replace('{{ namespace }}', $namespace, $content);

            File::put($appModelPath, $content);
            $this->info("App\Model.php has been created.");
        }

        // Continue with the parent handle method
        parent::handle();
    }
}
