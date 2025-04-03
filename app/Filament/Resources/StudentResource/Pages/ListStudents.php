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
        $tabs = [
            'all' => \Filament\Resources\Pages\ListRecords\Tab::make('All Students')
                ->badge(function () {
                    return Student::count();
                }),
        ];

        // Add a tab for each department
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

        return $tabs;
    }
}
