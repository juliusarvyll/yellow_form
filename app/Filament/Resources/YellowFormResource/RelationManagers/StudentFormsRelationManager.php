<?php

namespace App\Filament\Resources\YellowFormResource\RelationManagers;

use App\Models\YellowForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class StudentFormsRelationManager extends RelationManager
{
    protected static string $relationship = 'selfAndSiblings';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'All Yellow Forms for this Student';

    protected static bool $shouldBeLazyLoaded = false;

    /**
     * Display Debug Information in the header to help troubleshoot
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('debug_info')
                ->color('gray')
                ->label('Debug Info')
                ->action(function () {})
                ->modalContent(function (YellowForm $record) {
                    $studentIdNumber = $record->id_number;
                    $formCount = $record->getFormCountAttribute();
                    $allForms = YellowForm::where('id_number', $studentIdNumber)->get();

                    return view('filament.components.debug-info', [
                        'record' => $record,
                        'studentIdNumber' => $studentIdNumber,
                        'formCount' => $formCount,
                        'allForms' => $allForms,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->icon('heroicon-o-information-circle'),
        ];
    }

    public static function canViewForRecord(mixed $record, string $pageClass): bool
    {
        // Always show this relation if we have a valid student ID number
        return !empty($record->id_number);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        // Check if yellow_forms table has the new name columns
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        return $table
            ->recordTitleAttribute('id')
            ->query(function (YellowForm $record): Builder {
                // Get the record's ID number, ensuring it's trimmed
                $studentIdNumber = trim($record->id_number ?? '');

                // Log for debugging
                Log::info("Processing form #{$record->id} with student ID: '{$studentIdNumber}'");

                // 1. If ID number is empty, use just this record
                if (empty($studentIdNumber)) {
                    Log::warning("Empty ID number for form #{$record->id}, showing only this form");
                    return YellowForm::where('id', $record->id);
                }

                // 2. Get all forms with matching ID number
                $query = YellowForm::query()
                    ->where('id_number', $studentIdNumber)
                    ->orderBy('date', 'desc');

                $count = $query->count();
                Log::info("Found {$count} forms with ID: '{$studentIdNumber}'");

                // 3. If somehow nothing was found, just show this record
                if ($count === 0) {
                    Log::warning("No records found for ID: '{$studentIdNumber}', showing only current record");
                    return YellowForm::where('id', $record->id);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Form #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('violation.violation_name')
                    ->label('Violation')
                    ->sortable()
                    ->getStateUsing(function (?YellowForm $record): string {
                        if (!$record) {
                            return 'No Record';
                        }

                        // If there's no violation record, return 'No Violation'
                        if (!$record->violation) {
                            return 'No Violation';
                        }

                        $violationName = $record->violation->violation_name ?: 'Unnamed Violation #' . $record->violation->id;

                        // Check if this is an "Others" violation and has a custom description
                        if (($violationName === 'Others' || $violationName === 'Other') && $record->other_violation) {
                            return $violationName . ': ' . $record->other_violation;
                        }

                        return $violationName;
                    }),
                Tables\Columns\TextColumn::make('department.department_name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.course_name')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->searchable(),
                Tables\Columns\IconColumn::make('complied')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('dean_verification')
                    ->label('Dean Verified')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('head_approval')
                    ->label('Head Approved')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('faculty_signature')
                    ->label('Issued By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('suspension_status')
                    ->label('Suspension Status')
                    ->badge()
                    ->color(fn (?YellowForm $record): string => match(true) {
                        !$record => 'gray',
                        $record->isCurrentlySuspended() => 'danger',
                        $record->is_suspended && $record->suspension_end_date < now() => 'success',
                        $record->is_suspended && $record->suspension_start_date > now() => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('complied')
                    ->options([
                        '1' => 'Complied',
                        '0' => 'Not Complied',
                    ])
                    ->label('Compliance Status'),
                Tables\Filters\SelectFilter::make('is_suspended')
                    ->options([
                        '1' => 'Suspended',
                        '0' => 'Not Suspended',
                    ])
                    ->label('Suspension Status'),
            ])
            ->headerActions([
                // Since this is read-only, we don't need any actions
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Since this is read-only, we don't need any bulk actions
            ]);
    }
}
