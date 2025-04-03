<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'id_number',
        'last_name',
        'first_name',
        'middle_name',
        'department_id',
        'course_id',
        'year',
        'sex',
        'status',
    ];

    /**
     * Get the department that the student belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the course that the student belongs to.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the yellow forms associated with the student.
     */
    public function yellowForms(): HasMany
    {
        return $this->hasMany(YellowForm::class, 'id_number', 'id_number');
    }

    /**
     * Get the count of violations for this student
     */
    public function getViolationCountAttribute(): int
    {
        return $this->yellowForms()->count();
    }

    /**
     * Check if the student is a repeat offender (has multiple violations)
     */
    public function getIsRepeatOffenderAttribute(): bool
    {
        return $this->violation_count >= 2;
    }
}
