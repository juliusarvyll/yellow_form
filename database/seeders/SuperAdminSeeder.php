<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // Check if Super Admin role exists
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found! Please run RolesAndPermissionsSeeder first.');
            return;
        }

        // Email for the super admin user
        $email = $this->command->ask('Enter email for Super Admin', 'admin@example.com');

        // Check if user already exists
        $user = User::where('email', $email)->first();

        if ($user) {
            // User exists, assign Super Admin role if not already assigned
            if (!$user->hasRole('Super Admin')) {
                $user->assignRole('Super Admin');
                $this->command->info("Super Admin role assigned to existing user: {$user->name} ({$user->email})");
            } else {
                $this->command->info("User {$user->name} already has the Super Admin role.");
            }
        } else {
            // Create new user with Super Admin role
            $name = $this->command->ask('Enter name for Super Admin', 'Administrator');
            $password = $this->command->secret('Enter password for Super Admin (min 8 characters)');

            if (strlen($password) < 8) {
                $this->command->error('Password must be at least 8 characters long.');
                return;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $user->assignRole('Super Admin');

            $this->command->info('Super Admin user created successfully!');
            $this->command->info("Name: {$name}");
            $this->command->info("Email: {$email}");
            $this->command->info("Password: ********");
        }
    }
}
