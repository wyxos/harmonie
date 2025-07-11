<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallGitHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harmonie:install-git-hook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a git pre-push hook that runs PHP Artisan tests';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $gitDir = base_path('.git');
        $hooksDir = $gitDir . '\hooks';
        $prePushPath = $hooksDir . '\pre-push';

        // Check if .git directory exists
        if (!File::exists($gitDir)) {
            $this->error('Git repository not found. Make sure you are in a git repository.');
            return Command::FAILURE;
        }

        // Create hooks directory if it doesn't exist
        if (!File::exists($hooksDir)) {
            File::makeDirectory($hooksDir, 0755, true);
        }

        // Create pre-push hook content
        $hookContent = "#!/bin/sh\n\n";
        $hookContent .= "# Git hook installed by Harmonie package\n";
        $hookContent .= "echo \"Running tests before pushing...\"\n";
        $hookContent .= "php artisan test --parallel --compact\n\n";
        $hookContent .= "# Get the exit code of the tests\n";
        $hookContent .= "EXIT_CODE=\$?\n\n";
        $hookContent .= "# If tests failed, prevent the push\n";
        $hookContent .= "if [ \$EXIT_CODE -ne 0 ]; then\n";
        $hookContent .= "  echo \"Tests failed. Push aborted.\"\n";
        $hookContent .= "  exit 1\n";
        $hookContent .= "fi\n\n";
        $hookContent .= "# If tests passed, allow the push\n";
        $hookContent .= "echo \"Tests passed. Proceeding with push.\"\n";
        $hookContent .= "exit 0\n";

        // Write the hook file
        File::put($prePushPath, $hookContent);

        // Make the hook executable
        chmod($prePushPath, 0755);

        $this->info('Git pre-push hook installed successfully!');
        $this->info('Tests will now run automatically before each push.');

        return Command::SUCCESS;
    }
}
