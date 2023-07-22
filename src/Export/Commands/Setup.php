<?php

namespace Wyxos\Harmonie\Export\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Setup extends Command
{
    protected $signature = 'harmonie:setup';

    protected $description = 'Setup Harmonie package';

    public function handle()
    {
        if (! Schema::hasTable('job_batches')) {
            $this->call('queue:batches-table');
            $this->call('migrate');
        }
    }
}