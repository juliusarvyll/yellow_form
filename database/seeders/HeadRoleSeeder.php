<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class HeadRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create head role if it doesn't exist
        $headRole = Role::firstOrCreate(['name' => 'head']);

        // Define permissions for the head role
        $permissions = [
            // Basic permissions
            'view any yellow forms',
            'view yellow form',
            'update yellow form',

            // Special head role permissions
            'verify compliance',
            'verify dean verification',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $headRole->givePermissionTo($permission);
        }

        // For testing, you can assign the head role to a specific user
        // Uncomment the following lines and replace the email with an existing user's email
        /*
        $user = User::where('email', 'head@example.com')->first();
        if ($user) {
            $user->assignRole($headRole);
        }
        */

        $this->command->info('Head role and permissions created successfully!');
    }
}
