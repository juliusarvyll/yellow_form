<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Department;
use App\Models\User;

class DeanUserSeeder extends Seeder
{
    public function run()
    {
        // Create a dean user for each department
        $departments = Department::all();

        $this->command->info('=== Dean Panel Login Credentials ===');
        $this->command->info('URL: ' . env('APP_URL') . '/dean');

        foreach ($departments as $department) {
            $deanEmail = strtolower(str_replace(' ', '', $department->department_name)) . '.dean@example.com';

            User::create([
                'name' => $department->department_name . ' Dean',
                'email' => $deanEmail,
                'password' => Hash::make('password'),
                'department_id' => $department->id,
            ]);

            $this->command->info("Department: {$department->department_name}");
            $this->command->info("Email: {$deanEmail}");
            $this->command->info("Password: password");
            $this->command->info("----------------------------------");
        }
    }
}
