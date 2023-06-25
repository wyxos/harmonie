<?php

namespace Wyxos\Harmonie\Listing;

use Illuminate\Console\Command;
use function base_path;

class MakeListingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:listing {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a listing.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        $path = explode('/', $name);

        $stub = file_get_contents($this->getStub());

        $className = array_pop($path);

        $namespace = join('\\', array_merge(['App', $this->getRoot()], $path));

        $stub = str_replace('$namespace', $namespace, $stub);

        $stub = str_replace('$className', $className, $stub);

        $base = explode('\\', config("listing.base"));

        $use = join('\\', $base);

        $baseClass = $base[count($base) - 1]; // Assuming the base class is the last part of the 'listing.base' config

        $extend = $base[count($base) - 1];

        $stub = str_replace('$use', $use, $stub);

        $stub = str_replace('$baseClass', $baseClass, $stub); // New line

        $stub = str_replace('$extend', $extend, $stub);

        $directory = base_path(join('/', array_merge(['app', $this->getRoot()], $path)));

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($directory . "/$className.php", $stub);

        $this->info('Done');

        return 1;
    }

    protected function getStub(): string
    {
        return __DIR__ . '/../stubs/Listing.stub';
    }

    protected function getRoot(): string
    {
        return 'Listings';
    }
}
