<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('yellow_forms', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false);
            $table->date('suspension_start_date')->nullable();
            $table->date('suspension_end_date')->nullable();
            $table->text('suspension_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yellow_forms', function (Blueprint $table) {
            $table->dropColumn([
                'is_suspended',
                'suspension_start_date',
                'suspension_end_date',
                'suspension_notes'
            ]);
        });
    }
};
