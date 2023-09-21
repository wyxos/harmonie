<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;

class ClearAllCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cache forms (config, route, view, application, event, and optimize)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('event:clear');
        $this->call('optimize:clear');
        $this->call('horizon:terminate');

        $this->info('All cache forms cleared and horizon terminated!');

        return Command::SUCCESS;
    }
}
