<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YellowForm extends Model
{
    protected $fillable = [
        'id_number',
        'first_name',
        'middle_name',
        'last_name',
        'course_id',
        'department_id',
        'year',
        'date',
        'violation_id',
        'other_violation',
        'student_approval',
        'faculty_signature',
        'complied',
        'compliance_date',
        'dean_verification',
        'noted_by'
    ];

    protected $casts = [
        'date' => 'datetime',
        'compliance_date' => 'datetime',
        'student_approval' => 'boolean',
        'complied' => 'boolean',
        'dean_verification' => 'boolean',
    ];

    public function violation(): BelongsTo
    {
        return $this->belongsTo(Violation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get violation text with safe null checking
     */
    public function getViolationNameAttribute()
    {
        return $this->violation ? $this->violation->violation_name : null;
    }

    /**
     * Get the count of yellow forms for this student (including current one)
     */
    public function getFormCountAttribute(): int
    {
        return static::where('id_number', $this->id_number)->count();
    }

    /**
     * Get the count of previous yellow forms for this student (excluding current one)
     */
    public function getPreviousFormCountAttribute(): int
    {
        return static::where('id_number', $this->id_number)
            ->where('id', '!=', $this->id)
            ->count();
    }

    /**
     * Get all previous yellow forms for this student
     */
    public function getPreviousFormsAttribute()
    {
        return static::where('id_number', $this->id_number)
            ->where('id', '!=', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Scope a query to filter for repeat offenders
     */
    public function scopeRepeatOffenders($query, int $minCount = 2)
    {
        return $query->whereIn('id_number', function ($query) use ($minCount) {
            $query->select('id_number')
                ->from('yellow_forms')
                ->groupBy('id_number')
                ->havingRaw('COUNT(*) >= ?', [$minCount]);
        });
    }
}
