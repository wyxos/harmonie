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
        $enableExport = $this->confirm('Do you want to enable Harmonie exports?', true);

        if ($enableExport) {
            $this->call('harmonie:setup-exports');
            $this->updateConfig('export', true);
            $this->info('Exports have been enabled in the configuration.');
        } else {
            $this->updateConfig('export', false);
            $this->info('Exports have been disabled in the configuration.');
        }

        $enableImport = $this->confirm('Do you want to enable Harmonie imports?', true);

        if ($enableImport) {
            $this->call('harmonie:setup-imports');
            $this->updateConfig('import', true);
            $this->info('Imports have been enabled in the configuration.');
        } else {
            $this->updateConfig('import', false);
            $this->info('Imports have been disabled in the configuration.');
        }

        $this->call('vendor:publish', [
            '--tag' => 'harmonie:harmonie-config',
            '--force' => true
        ]);

        $this->info('Configuration has been published.');
    }

    protected function updateConfig($feature, $enabled)
    {
        $configPath = config_path('harmonie.php');
        $config = file_get_contents($configPath);

        // Update the config value
        $pattern = "/'$feature' => " . ($enabled ? 'false' : 'true') . "/";
        $replacement = "'$feature' => " . ($enabled ? 'true' : 'false');

        $config = preg_replace($pattern, $replacement, $config);

        file_put_contents($configPath, $config);
    }
}
