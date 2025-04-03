<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
        'faculty_signature',
        'user_id',
        'complied',
        'compliance_date',
        'dean_verification',
        'head_approval',
        'verification_notes'
    ];

    protected $casts = [
        'date' => 'datetime',
        'compliance_date' => 'datetime',
        'student_approval' => 'boolean',
        'complied' => 'boolean',
        'dean_verification' => 'boolean',
        'head_approval' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($yellowForm) {
            // Auto-set the user_id to the current authenticated user if not explicitly set
            if (!$yellowForm->user_id && Auth::check()) {
                $yellowForm->user_id = Auth::id();
            }

            // Auto-set the faculty_signature based on the user if not explicitly set
            if (!$yellowForm->faculty_signature && $yellowForm->user_id) {
                $user = User::find($yellowForm->user_id);
                if ($user) {
                    $yellowForm->faculty_signature = $user->name;
                }
            }
        });
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the faculty name from the user relation
     */
    public function getFacultyNameAttribute()
    {
        return $this->user ? $this->user->name : $this->faculty_signature;
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
