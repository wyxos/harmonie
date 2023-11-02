<?php

namespace Wyxos\Harmonie\Import\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Setup extends Command
{
    protected $signature = 'harmonie:setup-imports';

    protected $description = 'Setup Harmonie Import package';

    public function handle()
    {
        // Check if the configuration is published
        if (!file_exists(config_path('import.php'))) {
            $this->error("The configuration for Harmonie Import package has not been published.");

            if ($this->confirm("Do you want to publish it now?", true)) {
                $this->call('vendor:publish', ['--tag' => 'harmonie:import-config']);
            } else {
                $this->warn("Run 'artisan vendor:publish --tag=harmonie:import-config' command to publish it later.");
                return;
            }
        }

        // Check the load_migrations flag
        if (!config('import.load_migrations', false)) {
            if ($this->confirm("The 'load_migrations' flag in the 'import' configuration is set to false. Do you want to set it to true now?", true)) {
                // Get the content of the config file
                $configPath = config_path('import.php');
                $configContent = file_get_contents($configPath);

                // Replace the load_migrations flag to true
                $configContent = str_replace("'load_migrations' => false,", "'load_migrations' => true,", $configContent);

                // Save the content back to the file
                file_put_contents($configPath, $configContent);

                $this->info("The 'load_migrations' flag has been set to true.");
            } else {
                $this->warn("Please set 'load_migrations' to true manually to continue.");
                return;
            }
        }

        if (!Schema::hasTable('job_batches')) {
            $this->warn('Batch table not found. Generating migration.');

            $this->call('queue:batch');

            $this->info('Batch table migration table created.');
        }

        $this->info('Setup complete. Run "artisan migrate"');
    }
}
