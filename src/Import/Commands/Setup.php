<?php

namespace Wyxos\Harmonie\Import\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Setup extends Command
{
    protected $signature = 'harmonie:imports-setup';

    protected $description = 'Setup Harmonie Import package';

    public function handle()
    {
        if (! DB::table('job_batches')->exists()) {
            $this->call('queue:batches-table');
            $this->call('migrate');
        }

        if (! DB::table('imports')->exists()) {
            $this->call('migrate');
        }
    }
}