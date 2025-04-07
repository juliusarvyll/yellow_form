<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\YellowForm;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find all records with empty ID numbers and fix them
        $formsWithEmptyIds = DB::table('yellow_forms')
            ->whereNull('id_number')
            ->orWhere('id_number', '')
            ->get();

        foreach ($formsWithEmptyIds as $form) {
            // Generate a fallback ID using the form's actual ID
            $fallbackId = "TEMP-" . $form->id;

            // Update the record
            DB::table('yellow_forms')
                ->where('id', $form->id)
                ->update(['id_number' => $fallbackId]);

            echo "Fixed form #{$form->id} with temporary ID number: {$fallbackId}\n";
        }

        echo "Fixed " . count($formsWithEmptyIds) . " forms with empty ID numbers.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need a down method as it's fixing data
    }
};
