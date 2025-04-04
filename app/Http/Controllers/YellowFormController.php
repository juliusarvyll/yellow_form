<?php

namespace App\Http\Controllers;

use App\Models\YellowForm;
use App\Models\Violation;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class YellowFormController extends Controller
{
    /**
     * Store a newly created yellow form in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_number' => 'required|string|max:255|unique:yellow_forms',
            'name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'year' => 'required|string|max:255',
            'date' => 'required|date',
            'violation_id' => 'required|exists:violations,id',
            'student_approval' => 'boolean',
            'faculty_signature' => 'nullable|string|max:255',
            'complied' => 'boolean',
            'compliance_date' => 'nullable|date',
            'dean_verification' => 'boolean',
            'head_approval' => 'boolean',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        YellowForm::create($validated);

        return Redirect::route('dashboard')->with('success', 'Yellow form submitted successfully!');
    }

    /**
     * Get all violations for the API.
     */
    public function getViolations()
    {
        $violations = Violation::select('id', 'violation_legend as name', 'violation_description')
            ->orderBy('violation_legend')
            ->get();

        return response()->json($violations);
    }

    /**
     * Get all departments and their courses
     */
    public function getDepartmentsAndCourses()
    {
        $departments = Department::with('courses')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->department_name,
                    'courses' => $department->courses->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'name' => $course->course_name,
                        ];
                    })
                ];
            });

        return response()->json($departments);
    }

    /**
     * Search yellow forms by student ID.
     */
    public function search(Request $request)
    {
        $studentId = $request->query('student_id');

        $yellowForms = YellowForm::where('id_number', 'like', "%{$studentId}%")
            ->with(['violation', 'user', 'department', 'course'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($form) {
                return [
                    'id' => $form->id,
                    'student_id' => $form->id_number,
                    'student_name' => [
                        'first_name' => $form->first_name,
                        'middle_name' => $form->middle_name,
                        'last_name' => $form->last_name,
                    ],
                    'academic_info' => [
                        'course' => $form->course ? $form->course->course_name : 'N/A',
                        'department' => $form->department ? $form->department->department_name : 'N/A',
                        'year' => $form->year,
                    ],
                    'violation' => [
                        'name' => $form->violation->violation_name,
                        'legend' => $form->violation->violation_legend,
                        'description' => $form->violation->violation_description,
                        'other_violation' => $form->other_violation,
                    ],
                    'dates' => [
                        'created_at' => $form->created_at,
                        'date' => $form->date,
                        'compliance_date' => $form->compliance_date,
                    ],
                    'status' => [
                        'complied' => $form->complied,
                        'dean_verification' => $form->dean_verification,
                        'head_approval' => $form->head_approval,
                        'verification_notes' => $form->verification_notes,
                    ],
                    'suspension' => [
                        'is_suspended' => $form->is_suspended,
                        'suspension_status' => $form->suspension_status,
                        'suspension_start_date' => $form->suspension_start_date,
                        'suspension_end_date' => $form->suspension_end_date,
                        'suspension_notes' => $form->suspension_notes,
                        'remaining_days' => $form->getRemainingSuspensionDays(),
                    ],
                    'faculty' => [
                        'name' => $form->faculty_name,
                        'signature' => $form->faculty_signature,
                    ],
                ];
            });

        return response()->json($yellowForms);
    }
}
