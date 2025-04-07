<?php

namespace App\Filament\Resources\YellowFormResource\Pages;

use App\Filament\Resources\YellowFormResource;
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
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Create a custom infolist that only includes the fields we want to display
        return $infolist
            ->schema([
                // Include only the yellow form details sections here
                Infolists\Components\Section::make('Student Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id_number')
                            ->label('Student ID'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Student Name'),
                        Infolists\Components\TextEntry::make('department.department_name')
                            ->label('Department'),
                        Infolists\Components\TextEntry::make('course.course_name')
                            ->label('Course'),
                        Infolists\Components\TextEntry::make('year'),
                    ]),

                Infolists\Components\Section::make('Violation Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ->date(),
                        Infolists\Components\TextEntry::make('violation.violation_name')
                            ->label('Violation'),
                        Infolists\Components\IconEntry::make('complied')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('dean_verification')
                            ->label('Dean Verified')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('faculty_signature')
                            ->label('Reported By'),
                    ]),
            ]);
    }
}
