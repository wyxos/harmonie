<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Laravel\Scout\Searchable;

class ScoutReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:reset {--model=* : Specific models to reset (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect models using Scout, flush and reimport each';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $specifiedModels = $this->option('model');

        // If specific models are provided, use them
        if (!empty($specifiedModels)) {
            $this->processModels($specifiedModels);
            return Command::SUCCESS;
        }

        // Otherwise, detect models using Scout
        $models = $this->detectScoutModels();

        if (empty($models)) {
            $this->info("No models using Scout were detected.");
            return Command::SUCCESS;
        }

        $this->processModels($models);

        return Command::SUCCESS;
    }

    /**
     * Process the given models by flushing and reimporting them.
     *
     * @param array $models
     * @return void
     */
    protected function processModels(array $models)
    {
        $this->info("Processing " . count($models) . " models:");

        foreach ($models as $model) {
            $this->info("Resetting index for: " . $model);

            $this->info("  - Flushing...");
            Artisan::call('scout:flush', ['model' => $model]);

            $this->info("  - Importing...");
            Artisan::call('scout:import', ['model' => $model]);

            $this->info("  - Done!");
        }

        $this->info("All Scout indexes have been reset successfully!");
    }

    /**
     * Detect models that use the Laravel Scout Searchable trait.
     *
     * @return array
     */
    protected function detectScoutModels()
    {
        $this->info("Detecting models using Scout...");

        $models = [];

        // Get the application namespace
        $namespace = app()->getNamespace();

        // Get all PHP files in the Models directory
        $modelFiles = File::glob(app_path('Models') . '/*.php');

        foreach ($modelFiles as $file) {
            $className = $namespace . 'Models\\' . pathinfo($file, PATHINFO_FILENAME);

            // Skip if the class doesn't exist
            if (!class_exists($className)) {
                continue;
            }

            // Check if the class uses the Searchable trait
            if ($this->usesScoutSearchable($className)) {
                $models[] = $className;
                $this->info("  - Detected: " . $className);
            }
        }

        // Also check models directly in the app directory (legacy structure)
        $modelFiles = File::glob(app_path() . '/*.php');

        foreach ($modelFiles as $file) {
            $className = $namespace . pathinfo($file, PATHINFO_FILENAME);

            // Skip if the class doesn't exist
            if (!class_exists($className)) {
                continue;
            }

            // Check if the class uses the Searchable trait
            if ($this->usesScoutSearchable($className)) {
                $models[] = $className;
                $this->info("  - Detected: " . $className);
            }
        }

        return $models;
    }

    /**
     * Check if the given class uses the Laravel Scout Searchable trait.
     *
     * @param string $class
     * @return bool
     */
    protected function usesScoutSearchable($class)
    {
        try {
            $reflection = new ReflectionClass($class);

            // Check if the class uses the Searchable trait
            $traits = $this->getClassTraits($reflection);

            return in_array(Searchable::class, $traits) ||
                   in_array('Laravel\\Scout\\Searchable', $traits);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all traits used by a class including those used by parent classes.
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function getClassTraits(ReflectionClass $reflection)
    {
        $traits = [];

        // Get traits of the current class
        $traits = array_merge($traits, array_keys($reflection->getTraits()));

        // Get traits of parent classes
        if ($parent = $reflection->getParentClass()) {
            $traits = array_merge($traits, $this->getClassTraits($parent));
        }

        return $traits;
    }
}
