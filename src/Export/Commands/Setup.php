<?php

namespace Wyxos\Harmonie\Export\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Setup extends Command
{
    protected $signature = 'harmonie:setup';

    protected $description = 'Setup Harmonie package';

    public function handle()
    {
        if (! DB::table('job_batches')->exists()) {
            $this->call('queue:batches-table');
            $this->call('migrate');
        }
    }
}