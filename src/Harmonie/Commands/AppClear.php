<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AppClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application cache, config, views, routes and restart queue/horizon';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing application caches...');

        // Run the required artisan commands
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('route:clear');

        // Detect if Horizon is in use
        $usingHorizon = $this->isHorizonInstalled();

        if ($usingHorizon) {
            $this->info('Horizon detected, terminating...');
            $this->call('horizon:terminate');
        } else {
            $this->info('Queue system detected, restarting...');
            $this->call('queue:restart');
        }

        $this->info('All caches cleared and queue system restarted!');

        return Command::SUCCESS;
    }

    /**
     * Determine if Laravel Horizon is installed.
     *
     * @return bool
     */
    protected function isHorizonInstalled()
    {
        // Check if the Horizon class exists
        if (class_exists('Laravel\\Horizon\\Horizon')) {
            return true;
        }

        // Check if the Horizon service provider is registered in the application
        if (File::exists(base_path('config/horizon.php'))) {
            return true;
        }

        return false;
    }
}
