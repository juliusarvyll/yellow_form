<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AssignRolesToExistingDeansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find all users with department_id set (these are deans)
        $deans = User::whereNotNull('department_id')->get();

        $this->command->info('Assigning Dean role to existing dean users...');

        $count = 0;
        foreach ($deans as $dean) {
            // Skip if already has the role
            if ($dean->hasRole('Dean')) {
                continue;
            }

            $dean->assignRole('Dean');
            $count++;

            $this->command->info("Assigned Dean role to: {$dean->name} (ID: {$dean->id})");
        }

        $this->command->info("Finished! Assigned Dean role to {$count} existing dean users.");
    }
}
