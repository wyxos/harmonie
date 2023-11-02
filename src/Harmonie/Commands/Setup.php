<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Setup extends Command
{
    protected $signature = 'harmonie:setup';

    protected $description = 'Setup Harmonie';

    public function handle()
    {
        if ($this->confirm('Do you want to set up Harmonie exports?', true)) {
            $this->call('harmonie:setup-exports');
        }

        if ($this->confirm('Do you want to set up Harmonie imports?', true)) {
            $this->call('harmonie:setup-imports');
        }
    }
}
