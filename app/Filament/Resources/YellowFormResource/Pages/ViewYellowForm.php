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
        return parent::infolist($infolist)
            ->schema([
                // ... your existing infolist fields

                Infolists\Components\Section::make('Student History')
                    ->visible(fn($record) => $record->previous_form_count > 0)
                    ->schema([
                        Infolists\Components\TextEntry::make('previous_form_count')
                            ->label('Previous Yellow Forms')
                            ->badge()
                            ->color('danger'),

                        Infolists\Components\RepeatableEntry::make('previous_forms')
                            ->label('Form History')
                            ->schema([
                                Infolists\Components\TextEntry::make('date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('violation.violation_legend')
                                    ->label('Violation'),
                                Infolists\Components\IconEntry::make('complied')
                                    ->boolean(),
                            ])
                            ->columns(3)
                    ])
            ]);
    }
}
