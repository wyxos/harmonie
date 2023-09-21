<?php

namespace Wyxos\Harmonie\Export\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Setup extends Command
{
    protected $signature = 'harmonie:exports-setup';

    protected $description = 'Setup Harmonie Export package';

    public function handle()
    {
        if (! DB::table('job_batches')->exists()) {
            $this->call('queue:batches-table');
            $this->call('migrate');
        }

        if (! DB::table('exports')->exists()) {
            $this->call('migrate');
        }
    }
}