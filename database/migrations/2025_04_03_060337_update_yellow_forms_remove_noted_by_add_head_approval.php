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
            $table->dropColumn('noted_by');
            $table->boolean('head_approval')->default(false)->after('dean_verification');
            $table->timestamp('head_approval_date')->nullable()->after('head_approval');
            $table->text('verification_notes')->nullable()->after('head_approval_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yellow_forms', function (Blueprint $table) {
            $table->string('noted_by')->nullable();
            $table->dropColumn(['head_approval', 'head_approval_date', 'verification_notes']);
        });
    }
};
