<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            if (!Schema::hasColumn('violations', 'violation_legend')) {
                $table->string('violation_legend')->nullable();
            }
            if (!Schema::hasColumn('violations', 'violation_description')) {
                $table->text('violation_description')->nullable();
            }
        });

        // Migrate existing "Others" violation to use the proper legend
        DB::table('violations')
            ->where('violation_name', 'Others')
            ->update([
                'violation_legend' => 'OTHER',
                'violation_description' => 'Other violation (please specify)'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->dropColumn('violation_legend');
            $table->dropColumn('violation_description');
        });
    }
};
