<?php

namespace App\Filament\Resources\YellowFormResource\RelationManagers;

use App\Models\YellowForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relation;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Log;

class AllFormsRelationManager extends RelationManager
{
    protected static string $relationship = 'self';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'All Yellow Forms for this Student';

    // Add this method to directly resolve the relation
    public static function resolveRelationship(Model $ownerRecord): Relation
    {
        // Ensure we have the ID number
        if (empty($ownerRecord->id_number)) {
            Log::error("Cannot resolve relationship - ID number is empty for record {$ownerRecord->id}");
            // Return a relation with just this record
            return $ownerRecord->hasMany(YellowForm::class, 'id', 'id');
        }

        Log::info("Resolving relationship for {$ownerRecord->id_number}");
        return $ownerRecord->hasMany(YellowForm::class, 'id_number', 'id_number');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(function (Builder $query, YellowForm $ownerRecord): Builder {
                // Log for debugging
                Log::info("Current record ID: {$ownerRecord->id}");
                Log::info("Finding forms for student with ID number: '{$ownerRecord->id_number}'");

                // Safety check - if id_number is empty, just use the current record
                if (empty($ownerRecord->id_number)) {
                    Log::info("ID number is empty! Using single record with ID: {$ownerRecord->id}");
                    return YellowForm::where('id', $ownerRecord->id);
                }

                // Get all forms for this student using a direct query
                $studentForms = YellowForm::where('id_number', $ownerRecord->id_number)
                                    ->orderBy('date', 'desc')
                                    ->get();

                // Log form IDs for debugging
                $formIds = $studentForms->pluck('id')->toArray();
                Log::info("Found " . $studentForms->count() . " forms with IDs: " . implode(', ', $formIds));

                // Override the query completely with a direct approach
                return YellowForm::whereIn('id', $formIds)->orderBy('date', 'desc');
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
                // You can add filters if needed
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Student Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id_number')
                            ->label('Student ID'),
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('department.department_name')
                            ->label('Department'),
                        Infolists\Components\TextEntry::make('course.course_name')
                            ->label('Course'),
                        Infolists\Components\TextEntry::make('year'),
                        Infolists\Components\TextEntry::make('form_count')
                            ->label('Total Yellow Forms')
                            ->getStateUsing(function (?YellowForm $record): string {
                                if (!$record) return '0';

                                $totalForms = YellowForm::where('id_number', $record->id_number)->count();
                                return (string) $totalForms;
                            })
                            ->badge()
                            ->color(function (?YellowForm $record): string {
                                if (!$record) return 'gray';

                                $totalForms = YellowForm::where('id_number', $record->id_number)->count();
                                return match(true) {
                                    $totalForms >= 3 => 'danger',
                                    $totalForms == 2 => 'warning',
                                    default => 'success',
                                };
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Violation Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ->date()
                            ->label('Date of Violation'),
                        Infolists\Components\TextEntry::make('violation.violation_name')
                            ->label('Violation Type'),
                        Infolists\Components\TextEntry::make('other_violation')
                            ->label('Specific Violation')
                            ->visible(fn (?YellowForm $record): bool =>
                                $record &&
                                $record->violation &&
                                in_array($record->violation->violation_name, ['Others', 'Other']) &&
                                $record->other_violation
                            ),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\IconEntry::make('complied')
                            ->boolean()
                            ->label('Complied'),
                        Infolists\Components\IconEntry::make('dean_verification')
                            ->boolean()
                            ->label('Dean Verified'),
                        Infolists\Components\IconEntry::make('head_approval')
                            ->boolean()
                            ->label('Head Approved'),
                        Infolists\Components\TextEntry::make('faculty_signature')
                            ->label('Issued By'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Suspension Information')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_suspended')
                            ->boolean()
                            ->label('Is Suspended'),
                        Infolists\Components\TextEntry::make('suspension_start_date')
                            ->date()
                            ->label('Suspension Start Date'),
                        Infolists\Components\TextEntry::make('suspension_end_date')
                            ->date()
                            ->label('Suspension End Date'),
                        Infolists\Components\TextEntry::make('suspension_notes')
                            ->label('Suspension Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn (?YellowForm $record): bool => $record ? $record->is_suspended : false),
            ]);
    }
}
