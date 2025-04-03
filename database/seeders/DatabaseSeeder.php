<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DeanUserSeeder;
use Database\Seeders\YellowFormSeeder;
use Database\Seeders\ViolationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\HeadRoleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolesAndPermissionsSeeder::class, // Run this first to set up roles and permissions
            HeadRoleSeeder::class, // Add the head role
            DepartmentSeeder::class,
            CourseSeeder::class,
            ViolationSeeder::class,
            DeanUserSeeder::class,
            // Add other seeders here
        ]);
    }
}
