<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\YellowForm;
use App\Models\Course;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DeanDashboardWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public static function shouldRegister(): bool
    {
        // Only register this widget in the dean panel
        return str_contains(request()->path(), 'dean');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $departmentId = $user ? $user->department_id : null;

        if (!$departmentId) {
            return [
                Stat::make('Department', 'Not assigned')
                    ->description('No department assigned to your account')
                    ->color('danger'),
            ];
        }

        // Get department stats
        $totalStudents = Student::where('department_id', $departmentId)->count();
        $totalYellowForms = YellowForm::where('department_id', $departmentId)->count();
        $pendingYellowForms = YellowForm::where('department_id', $departmentId)
            ->where(function ($query) {
                $query->where('dean_verification', false)
                    ->orWhereNull('dean_verification');
            })
            ->count();

        $repeatOffenders = YellowForm::where('department_id', $departmentId)
            ->select('id_number')
            ->groupBy('id_number')
            ->havingRaw('COUNT(*) >= ?', [2])
            ->count();

        $courseCount = Course::where('department_id', $departmentId)->count();

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('Students in your department')
                ->color('success'),

            Stat::make('Yellow Forms', $totalYellowForms)
                ->description('Total violation forms')
                ->color('warning'),

            Stat::make('Pending Approvals', $pendingYellowForms)
                ->description('Forms requiring your verification')
                ->color($pendingYellowForms > 0 ? 'danger' : 'success'),

            Stat::make('Repeat Offenders', $repeatOffenders)
                ->description('Students with 2+ violations')
                ->color($repeatOffenders > 0 ? 'danger' : 'success'),

            Stat::make('Courses', $courseCount)
                ->description('Courses in your department')
                ->color('info'),
        ];
    }
}
