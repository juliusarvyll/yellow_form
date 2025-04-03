<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Violation;
use Illuminate\Support\Facades\Schema;

class ViolationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create standard violations
        $standardViolations = [
            'Haircut/ punky Hair(Male)',
            'Dyed Hair(Male/Female)',
            'Piercings(Male/Female)',
            'Unprescribed undergarment(Male/Female)',
            'Unprescribed Shoes(Male/Female)',
            'Long/short skirt(Female)',
            'Being Noisy along corridors',
            'Not wearing ID properly',
            'Earrings(Male)/Tongue piercing(Male/Female)',
            'Wearing of cap inside the campus',
        ];

        foreach ($standardViolations as $violation) {
            Violation::firstOrCreate([
                'violation_name' => $violation
            ]);
        }

        // Create "Others" option - this will be used for custom violations
        Violation::firstOrCreate([
            'violation_name' => 'Others'
        ]);

        // After we've run the migration to add the new columns, update the records
        if (Schema::hasColumn('violations', 'violation_legend')) {
            // Update the "Others" record with the proper legend
            Violation::where('violation_name', 'Others')
                ->update([
                    'violation_legend' => 'OTHER',
                    'violation_description' => 'Other violation (please specify)'
                ]);
        }

    }
}
