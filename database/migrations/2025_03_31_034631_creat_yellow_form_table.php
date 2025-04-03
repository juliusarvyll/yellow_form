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
        Schema::create('yellow_forms', function (Blueprint $table) {
            $table->id();
            $table->string('id_number');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('year');
            $table->date('date');
            $table->foreignId('violation_id')->constrained('violations');
            $table->string('other_violation')->nullable();
            $table->boolean('student_approval')->default(false);
            $table->string('faculty_signature')->nullable();
            $table->boolean('complied')->default(false);
            $table->date('compliance_date')->nullable();
            $table->boolean('dean_verification')->default(false);
            $table->string('noted_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yellow_forms');
    }
};
