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
        'verification_notes',
        'is_suspended',
        'suspension_start_date',
        'suspension_end_date',
        'suspension_notes'
    ];

    protected $casts = [
        'date' => 'datetime',
        'compliance_date' => 'datetime',
        'student_approval' => 'boolean',
        'complied' => 'boolean',
        'dean_verification' => 'boolean',
        'head_approval' => 'boolean',
        'is_suspended' => 'boolean',
        'suspension_start_date' => 'date',
        'suspension_end_date' => 'date'
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

            // Check if student is currently suspended
            if (static::isStudentCurrentlySuspended($yellowForm->id_number)) {
                throw new \Exception('Cannot create new yellow form: Student is currently suspended.');
            }

            // Get all previous yellow forms for this student
            $previousFormsCount = static::where('id_number', $yellowForm->id_number)->count();

            // Only apply suspension if this is the third violation
            if ($previousFormsCount === 2) {
                // This is exactly the third yellow form, trigger automatic suspension
                $yellowForm->is_suspended = true;
                $yellowForm->suspension_start_date = now();
                $yellowForm->suspension_end_date = now()->addDays(7); // 1 week suspension
                $yellowForm->suspension_notes = 'Automatic suspension: Student has accumulated 3 yellow forms.';

                // Notify relevant users about the suspension
                if (class_exists('\Filament\Notifications\Notification')) {
                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('Student Automatically Suspended')
                        ->body("Student {$yellowForm->id_number} has been suspended for 7 days after receiving their third yellow form.")
                        ->send();
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

    /**
     * Check if a student is currently suspended
     */
    public static function isStudentCurrentlySuspended(string $id_number): bool
    {
        $violationCount = static::where('id_number', $id_number)->count();

        // First check if student has enough violations to be suspended
        if ($violationCount < 3) {
            return false;
        }

        // Then check if there's an active suspension
        return static::where('id_number', $id_number)
            ->where('is_suspended', true)
            ->where('suspension_start_date', '<=', now())
            ->where('suspension_end_date', '>=', now())
            ->exists();
    }

    /**
     * Get all currently suspended students
     */
    public static function getCurrentlySuspendedStudents()
    {
        return static::where('is_suspended', true)
            ->where('suspension_start_date', '<=', now())
            ->where('suspension_end_date', '>=', now())
            ->whereIn('id_number', function ($query) {
                $query->select('id_number')
                    ->from('yellow_forms')
                    ->groupBy('id_number')
                    ->havingRaw('COUNT(*) >= ?', [3]);
            })
            ->get();
    }

    /**
     * Check if this specific yellow form record is under active suspension
     */
    public function isCurrentlySuspended(): bool
    {
        return $this->is_suspended &&
            $this->suspension_start_date <= now() &&
            $this->suspension_end_date >= now();
    }

    /**
     * Get remaining suspension days
     */
    public function getRemainingSuspensionDays(): ?int
    {
        if (!$this->is_suspended || !$this->suspension_end_date) {
            return null;
        }

        $endDate = $this->suspension_end_date->startOfDay();
        $today = now()->startOfDay();

        if ($endDate < $today) {
            return 0;
        }

        return $endDate->diffInDays($today);
    }

    /**
     * Get suspension status text
     */
    public function getSuspensionStatusAttribute(): string
    {
        if (!$this->is_suspended) {
            return 'Not Suspended';
        }

        if ($this->isCurrentlySuspended()) {
            $remainingDays = $this->getRemainingSuspensionDays();
            return "Suspended ({$remainingDays} days remaining)";
        }

        if ($this->suspension_end_date < now()) {
            return 'Suspension Completed';
        }

        if ($this->suspension_start_date > now()) {
            return 'Suspension Pending';
        }

        return 'Suspended';
    }
}
