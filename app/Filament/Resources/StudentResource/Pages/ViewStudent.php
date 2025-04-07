<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\YellowFormResource;
use App\Models\YellowForm;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $studentIdNumber = $this->record->id_number;

        // Get all yellow forms for this student
        $yellowForms = YellowForm::where('id_number', $studentIdNumber)
            ->orderBy('date', 'desc')
            ->get();

        $formCount = $yellowForms->count();

        return parent::infolist($infolist)
            ->schema([
                // Keep existing student information sections

                // Add a dedicated section for all yellow forms
                Infolists\Components\Section::make('Disciplinary Records')
                    ->description(new HtmlString("Student has <strong>{$formCount}</strong> yellow form(s) on record."))
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn() => $formCount > 0)
                    ->schema([
                        ...$yellowForms->map(function($form) {
                            return Infolists\Components\Card::make()
                                ->columns(2)
                                ->columnSpanFull()
                                ->schema([
                                    // Header with date and form ID
                                    Infolists\Components\TextEntry::make("form_{$form->id}_header")
                                        ->label('')
                                        ->state(function() use ($form) {
                                            $dateFormatted = $form->date->format('F d, Y');
                                            $violationName = $form->violation ?
                                                (($form->violation->violation_name === 'Others' || $form->violation->violation_name === 'Other') && $form->other_violation ?
                                                    "{$form->violation->violation_name}: {$form->other_violation}" :
                                                    $form->violation->violation_name) :
                                                'No Violation';

                                            return new HtmlString("<span class='text-lg font-bold'>{$violationName}</span><br><span class='text-sm text-gray-500'>Date: {$dateFormatted} (Form #{$form->id})</span>");
                                        })
                                        ->columnSpanFull(),

                                    // Status indicators
                                    Infolists\Components\TextEntry::make("form_{$form->id}_complied")
                                        ->label('Complied')
                                        ->badge()
                                        ->color($form->complied ? 'success' : 'danger')
                                        ->state($form->complied ? 'Yes' : 'No'),

                                    Infolists\Components\TextEntry::make("form_{$form->id}_dean_verified")
                                        ->label('Dean Verified')
                                        ->badge()
                                        ->color($form->dean_verification ? 'success' : 'warning')
                                        ->state($form->dean_verification ? 'Yes' : 'Pending'),

                                    // Faculty information
                                    Infolists\Components\TextEntry::make("form_{$form->id}_faculty")
                                        ->label('Reported By')
                                        ->state($form->faculty_signature),

                                    // Actions for this form
                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('viewForm')
                                            ->label('View Form')
                                            ->url(YellowFormResource::getUrl('view', ['record' => $form]))
                                            ->icon('heroicon-o-eye')
                                            ->color('primary')
                                            ->button(),

                                        Infolists\Components\Actions\Action::make('editForm')
                                            ->label('Edit Form')
                                            ->url(YellowFormResource::getUrl('edit', ['record' => $form]))
                                            ->icon('heroicon-o-pencil')
                                            ->color('gray')
                                            ->button(),
                                    ])
                                    ->columnSpanFull(),
                                ]);
                        })->toArray()
                    ])
                    ->collapsible(),
            ]);
    }
}
