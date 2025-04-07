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
        // First check if the table has the separate name columns
        if (Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name'])) {
            // Create a temporary table with the desired structure
            Schema::create('temp_yellow_forms', function (Blueprint $table) {
                $table->id();
                $table->string('id_number');
                $table->string('name');
                $table->foreignId('course_id')->constrained('courses');
                $table->foreignId('department_id')->constrained('departments');
                $table->string('year');
                $table->date('date');
                $table->foreignId('violation_id')->constrained('violations');
                $table->string('other_violation')->nullable();
                $table->string('faculty_signature')->nullable();
                $table->boolean('complied')->default(false);
                $table->date('compliance_date')->nullable();
                $table->boolean('dean_verification')->default(false);
                $table->boolean('head_approval')->default(false);
                $table->timestamp('head_approval_date')->nullable();
                $table->text('verification_notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->boolean('is_suspended')->default(false);
                $table->date('suspension_start_date')->nullable();
                $table->date('suspension_end_date')->nullable();
                $table->text('suspension_notes')->nullable();
                $table->timestamps();
            });

            // Copy data from yellow_forms to temp_yellow_forms, combining the name fields
            $forms = DB::table('yellow_forms')->get();
            foreach ($forms as $form) {
                $name = trim("{$form->first_name} {$form->middle_name} {$form->last_name}");

                DB::table('temp_yellow_forms')->insert([
                    'id' => $form->id,
                    'id_number' => $form->id_number,
                    'name' => $name,
                    'course_id' => $form->course_id,
                    'department_id' => $form->department_id,
                    'year' => $form->year,
                    'date' => $form->date,
                    'violation_id' => $form->violation_id,
                    'other_violation' => $form->other_violation,
                    'faculty_signature' => $form->faculty_signature,
                    'complied' => $form->complied,
                    'compliance_date' => $form->compliance_date,
                    'dean_verification' => $form->dean_verification,
                    'head_approval' => $form->head_approval,
                    'head_approval_date' => $form->head_approval_date,
                    'verification_notes' => $form->verification_notes,
                    'user_id' => $form->user_id,
                    'is_suspended' => $form->is_suspended,
                    'suspension_start_date' => $form->suspension_start_date,
                    'suspension_end_date' => $form->suspension_end_date,
                    'suspension_notes' => $form->suspension_notes,
                    'created_at' => $form->created_at,
                    'updated_at' => $form->updated_at,
                ]);
            }

            // Drop the original yellow_forms table
            Schema::drop('yellow_forms');

            // Rename temp_yellow_forms to yellow_forms
            Schema::rename('temp_yellow_forms', 'yellow_forms');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('yellow_forms', 'name') && !Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name'])) {
            // Create a temporary table with the old structure
            Schema::create('temp_yellow_forms', function (Blueprint $table) {
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
                $table->string('faculty_signature')->nullable();
                $table->boolean('complied')->default(false);
                $table->date('compliance_date')->nullable();
                $table->boolean('dean_verification')->default(false);
                $table->boolean('head_approval')->default(false);
                $table->timestamp('head_approval_date')->nullable();
                $table->text('verification_notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->boolean('is_suspended')->default(false);
                $table->date('suspension_start_date')->nullable();
                $table->date('suspension_end_date')->nullable();
                $table->text('suspension_notes')->nullable();
                $table->timestamps();
            });

            // Copy data from yellow_forms to temp_yellow_forms, splitting the name field
            $forms = DB::table('yellow_forms')->get();
            foreach ($forms as $form) {
                $nameParts = explode(' ', $form->name);
                $firstName = $nameParts[0] ?? '';
                $lastName = array_pop($nameParts) ?? '';
                $middleName = implode(' ', $nameParts) ?? '';

                DB::table('temp_yellow_forms')->insert([
                    'id' => $form->id,
                    'id_number' => $form->id_number,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'course_id' => $form->course_id,
                    'department_id' => $form->department_id,
                    'year' => $form->year,
                    'date' => $form->date,
                    'violation_id' => $form->violation_id,
                    'other_violation' => $form->other_violation,
                    'faculty_signature' => $form->faculty_signature,
                    'complied' => $form->complied,
                    'compliance_date' => $form->compliance_date,
                    'dean_verification' => $form->dean_verification,
                    'head_approval' => $form->head_approval,
                    'head_approval_date' => $form->head_approval_date,
                    'verification_notes' => $form->verification_notes,
                    'user_id' => $form->user_id,
                    'is_suspended' => $form->is_suspended,
                    'suspension_start_date' => $form->suspension_start_date,
                    'suspension_end_date' => $form->suspension_end_date,
                    'suspension_notes' => $form->suspension_notes,
                    'created_at' => $form->created_at,
                    'updated_at' => $form->updated_at,
                ]);
            }

            // Drop the original yellow_forms table
            Schema::drop('yellow_forms');

            // Rename temp_yellow_forms to yellow_forms
            Schema::rename('temp_yellow_forms', 'yellow_forms');
        }
    }
};
