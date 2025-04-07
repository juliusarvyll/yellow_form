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

class OtherFormsRelationManager extends RelationManager
{
    protected static string $relationship = 'siblings';

    protected static ?string $recordTitleAttribute = 'id_number';

    protected static ?string $title = 'Other Yellow Forms for this Student';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        // Check if yellow_forms table has the new name columns
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        return $table
            ->recordTitleAttribute('id_number')
            ->modifyQueryUsing(function (Builder $query, YellowForm $ownerRecord): Builder {
                // Write debug info to logs
                info("Showing siblings for form ID: {$ownerRecord->id}, Student ID: {$ownerRecord->id_number}");

                // Count how many related records we should have
                $relatedCount = YellowForm::where('id_number', $ownerRecord->id_number)
                    ->where('id', '!=', $ownerRecord->id)
                    ->count();

                info("Found {$relatedCount} related yellow forms");

                return $query->latest();
            })
            ->columns([
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
                Tables\Columns\IconColumn::make('complied')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('faculty_signature')
                    ->label('Issued By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // You can add filters here if needed
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
