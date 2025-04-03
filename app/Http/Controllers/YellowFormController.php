<?php

namespace App\Http\Controllers;

use App\Models\YellowForm;
use App\Models\Violation;
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
            'noted_by' => 'nullable|string|max:255',
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
}
