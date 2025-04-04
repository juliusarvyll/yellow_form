<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $isDean = $user && $user->hasRole('Dean');

        $tabs = [
            'all' => \Filament\Resources\Pages\ListRecords\Tab::make('All')
                ->badge(function () use ($user, $isDean) {
                    $query = Student::query();
                    if ($isDean) {
                        $query->where('department_id', $user->department_id);
                    }
                    return $query->count();
                }),
            'suspended' => \Filament\Resources\Pages\ListRecords\Tab::make('Suspended')
                ->badge(function () use ($user, $isDean) {
                    $query = Student::where('is_suspended', true);
                    if ($isDean) {
                        $query->where('department_id', $user->department_id);
                    }
                    return $query->count();
                })
                ->modifyQueryUsing(function ($query) use ($user, $isDean) {
                    $query->where('is_suspended', true);
                    if ($isDean) {
                        $query->where('department_id', $user->department_id);
                    }
                    return $query;
                }),
        ];

        // If user is dean, show courses from their department
        if ($isDean) {
            $courses = Course::where('department_id', $user->department_id)
                ->orderBy('course_name')
                ->get();

            foreach ($courses as $course) {
                $studentCount = Student::where('course_id', $course->id)->count();

                // Only add tabs for courses that have students
                if ($studentCount > 0) {
                    $tabs[$course->id] = \Filament\Resources\Pages\ListRecords\Tab::make($course->course_abbreviation)
                        ->modifyQueryUsing(fn ($query) => $query->where('course_id', $course->id))
                        ->badge($studentCount);
                }
            }
        } else {
            // For non-dean users, show department tabs
            $departments = Department::orderBy('department_name')->get();

            foreach ($departments as $department) {
                $studentCount = Student::where('department_id', $department->id)->count();

                // Only add tabs for departments that have students
                if ($studentCount > 0) {
                    $tabs[$department->id] = \Filament\Resources\Pages\ListRecords\Tab::make($department->department_name)
                        ->modifyQueryUsing(fn ($query) => $query->where('department_id', $department->id))
                        ->badge($studentCount);
                }
            }
        }

        return $tabs;
    }
}
