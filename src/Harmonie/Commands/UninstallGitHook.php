<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UninstallGitHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harmonie:uninstall-git-hook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall the git pre-push hook that runs PHP Artisan tests';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $gitDir = base_path('.git');
        $prePushPath = $gitDir . '\hooks\pre-push';

        // Check if .git directory exists
        if (!File::exists($gitDir)) {
            $this->error('Git repository not found. Make sure you are in a git repository.');
            return Command::FAILURE;
        }

        // Check if the pre-push hook exists
        if (!File::exists($prePushPath)) {
            $this->info('Git pre-push hook not found. Nothing to uninstall.');
            return Command::SUCCESS;
        }

        // Check if it's our hook by reading the file content
        $hookContent = File::get($prePushPath);
        if (strpos($hookContent, 'Git hook installed by Harmonie package') === false) {
            $this->warn('The existing pre-push hook was not installed by Harmonie package.');
            if (!$this->confirm('Do you want to remove it anyway?', false)) {
                $this->info('Uninstallation aborted.');
                return Command::SUCCESS;
            }
        }

        // Remove the hook file
        File::delete($prePushPath);

        $this->info('Git pre-push hook uninstalled successfully!');
        $this->info('Tests will no longer run automatically before each push.');

        return Command::SUCCESS;
    }
}
