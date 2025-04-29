<?php

namespace App\Filament\Resources\Dean\YellowFormResource\Pages;

use App\Filament\Resources\Dean\YellowFormResource;
use App\Models\Student;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewYellowForm extends ViewRecord
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Approve'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Student Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id_number')
                            ->label('Student ID Number'),
                        Infolists\Components\TextEntry::make('student_name')
                            ->label('Name')
                            ->getStateUsing(function ($record) {
                                $student = Student::where('id_number', $record->id_number)->first();
                                if ($student) {
                                    return trim("{$student->first_name} {$student->middle_name} {$student->last_name}");
                                }
                                return trim("{$record->first_name} {$record->middle_name} {$record->last_name}");
                            }),
                        Infolists\Components\TextEntry::make('department.department_name')
                            ->label('Department'),
                        Infolists\Components\TextEntry::make('course.course_name')
                            ->label('Course'),
                        Infolists\Components\TextEntry::make('year'),
                        Infolists\Components\TextEntry::make('date')
                            ->label('Violation Date')
                            ->date(),
                    ])->columns(2),

                Infolists\Components\Section::make('Violation Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('violation.violation_name')
                            ->label('Violation'),
                        Infolists\Components\TextEntry::make('other_violation')
                            ->label('Violation Description')
                            ->visible(fn ($record) => $record->violation && $record->violation->violation_name === 'Others'),
                    ]),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('faculty_signature')
                            ->label('Faculty Name'),
                        Infolists\Components\IconEntry::make('complied')
                            ->label('Student Complied')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('compliance_date')
                            ->date(),
                    ])->columns(2),
            ]);
    }
}
