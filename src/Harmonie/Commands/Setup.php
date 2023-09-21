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
        $this->call('harmonie:exports-setup');
        $this->call('harmonie:imports-setup');
    }
}