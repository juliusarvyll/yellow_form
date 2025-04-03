<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignSuperAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:super-admin {email : The email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the Super Admin role to a user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Check if Super Admin role exists
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if (!$superAdminRole) {
            $this->error("Super Admin role not found. Please run database seeders first.");
            return 1;
        }

        // Assign the role
        if ($user->hasRole('Super Admin')) {
            $this->info("User {$user->name} already has the Super Admin role.");
            return 0;
        }

        $user->assignRole('Super Admin');

        $this->info("Successfully assigned Super Admin role to {$user->name} ({$user->email}).");

        return 0;
    }
}
