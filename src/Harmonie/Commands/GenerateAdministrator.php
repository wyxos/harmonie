<?php

namespace Wyxos\Harmonie\Harmonie\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GenerateAdministrator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {email : The email of the user} {name : The name of the user} {--role= : The role of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an administrator';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $role = $this->option('role') ?? 'system administrator';  // Use the provided role or default to 'system administrator'

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required']
        ]);

        if ($validator->fails()) {
            $this->info("Validation failed. " . $validator->errors()->first());
            return Command::FAILURE;
        }

        if (!class_exists(\Spatie\Permission\Models\Role::class)) {
            $this->info("Spatie Role class does not exist. Please install the package.");
            return Command::FAILURE;
        }

        $password = Str::random(8);

        $userData = [
            'email' => $email,
            'email_verified_at' => Carbon::now(),
            'password' => bcrypt($password),
        ];

        if (Schema::hasColumn('users', 'name')) {
            $userData['name'] = $name;
        }

        $user = User::query()->create($userData);

        $user->assignRole(Role::findOrCreate($role));

        $this->info("User $name created with email $email and password $password");

        return Command::SUCCESS;
    }
}
