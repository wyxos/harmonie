<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class Toggle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harmonie:toggle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle between local and live versions of the harmonie package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $composerJsonPath = base_path('composer.json');
        
        if (!File::exists($composerJsonPath)) {
            $this->error('composer.json file not found!');
            return Command::FAILURE;
        }
        
        $composerJson = json_decode(File::get($composerJsonPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse composer.json: ' . json_last_error_msg());
            return Command::FAILURE;
        }
        
        // Check if using local version
        $usingLocalVersion = false;
        
        if (isset($composerJson['repositories'])) {
            foreach ($composerJson['repositories'] as $repository) {
                if (isset($repository['type']) && $repository['type'] === 'path' && 
                    isset($repository['url']) && strpos($repository['url'], 'wyxos/php/harmonie') !== false) {
                    $usingLocalVersion = true;
                    break;
                }
            }
        }
        
        if ($usingLocalVersion) {
            // Switch to live version
            $this->info('Currently using local version. Switching to live version...');
            
            // Remove the path repository
            $repositories = [];
            foreach ($composerJson['repositories'] as $repository) {
                if (!(isset($repository['type']) && $repository['type'] === 'path' && 
                    isset($repository['url']) && strpos($repository['url'], 'wyxos/php/harmonie') !== false)) {
                    $repositories[] = $repository;
                }
            }
            
            if (empty($repositories)) {
                unset($composerJson['repositories']);
            } else {
                $composerJson['repositories'] = $repositories;
            }
            
            // Get the latest version from packagist
            $this->info('Fetching latest version from packagist...');
            $process = Process::fromShellCommandline('composer show wyxos/harmonie --latest --format=json');
            $process->run();
            
            if (!$process->isSuccessful()) {
                $this->error('Failed to fetch latest version: ' . $process->getErrorOutput());
                return Command::FAILURE;
            }
            
            $packageInfo = json_decode($process->getOutput(), true);
            $latestVersion = $packageInfo['latest'] ?? '*';
            
            // Update the require section
            $composerJson['require']['wyxos/harmonie'] = $latestVersion;
            
            $this->info("Setting wyxos/harmonie to version: {$latestVersion}");
        } else {
            // Switch to local version
            $this->info('Currently using live version. Switching to local version...');
            
            // Add the path repository
            if (!isset($composerJson['repositories'])) {
                $composerJson['repositories'] = [];
            }
            
            $composerJson['repositories'][] = [
                'type' => 'path',
                'url' => '../../composer/harmonie'
            ];
            
            // Update the require section to use any version
            $composerJson['require']['wyxos/harmonie'] = '*';
            
            $this->info('Setting wyxos/harmonie to use local path repository');
        }
        
        // Save the updated composer.json
        File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        // Run composer update
        $this->info('Running composer update wyxos/harmonie...');
        $process = Process::fromShellCommandline('composer update wyxos/harmonie');
        $process->setTimeout(null);
        $process->setTty(true);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        
        if (!$process->isSuccessful()) {
            $this->error('Composer update failed!');
            return Command::FAILURE;
        }
        
        $this->info('Successfully toggled harmonie package!');
        
        return Command::SUCCESS;
    }
}
