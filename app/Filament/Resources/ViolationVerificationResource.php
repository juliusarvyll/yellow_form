<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ViolationVerificationResource\Pages;
use App\Models\YellowForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Collection;
use Closure;
use Illuminate\Http\Request;

class ViolationVerificationResource extends Resource
{
    protected static ?string $model = YellowForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Verification';

    protected static ?string $modelLabel = 'Violation Verification';

    protected static ?string $pluralModelLabel = 'Violation Verifications';

    protected static ?int $navigationSort = 1;

    // Only allow the head role to access this resource
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('head');
    }

    public static function form(Form $form): Form
    {
        // Check if yellow_forms table has the new name columns
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('id_number')
                            ->label('Student ID Number')
                            ->disabled(),

                        // Name fields based on database structure
                        $hasNameColumns
                            ? Forms\Components\Group::make([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('First Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('middle_name')
                                    ->label('Middle Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->disabled(),
                              ])
                            : Forms\Components\TextInput::make('name')
                                ->disabled(),

                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'department_name')
                            ->disabled(),
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'course_name')
                            ->disabled(),
                        Forms\Components\TextInput::make('year')
                            ->disabled(),
                        Forms\Components\DatePicker::make('date')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Violation Information')
                    ->schema([
                        Forms\Components\Select::make('violation_id')
                            ->relationship('violation', 'violation_name')
                            ->disabled(),
                        Forms\Components\TextInput::make('other_violation')
                            ->label('Other Violation Details')
                            ->disabled()
                            ->visible(fn ($record) => $record->violation?->violation_name === 'Others' || $record->violation?->violation_name === 'Other'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Verification')
                    ->schema([
                        Forms\Components\Toggle::make('complied')
                            ->label('Student Complied')
                            ->helperText('Verify if the student has complied with requirements'),
                        Forms\Components\DatePicker::make('compliance_date')
                            ->label('Compliance Date')
                            ->helperText('Date when the student complied'),
                        Forms\Components\Toggle::make('dean_verification')
                            ->label('Dean Verification')
                            ->helperText('Confirm dean verification'),
                        Forms\Components\Toggle::make('head_approval')
                            ->label('Head Approval')
                            ->helperText('Approve or disapprove this violation verification')
                            ->visible(fn () => auth()->user()->hasRole('head')),
                        Forms\Components\DateTimePicker::make('head_approval_date')
                            ->label('Head Approval Date')
                            ->helperText('Date and time when the head approved')
                            ->visible(fn () => auth()->user()->hasRole('head'))
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->placeholder('Add any notes regarding the verification process'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Check if yellow_forms table has the new name columns
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_number')
                    ->searchable()
                    ->sortable(),

                // Name column based on database structure
                $hasNameColumns
                    ? Tables\Columns\TextColumn::make('full_name')
                        ->label('Name')
                        ->getStateUsing(function (YellowForm $record): string {
                            return trim("{$record->first_name} {$record->middle_name} {$record->last_name}");
                        })
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                    : Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),

                Tables\Columns\TextColumn::make('department.department_name')
                    ->label('Department'),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('date')
                    ->date(),
                Tables\Columns\TextColumn::make('violation.violation_name')
                    ->label('Violation')
                    ->getStateUsing(function (YellowForm $record): string {
                        if (!$record->violation) {
                            return 'No Violation';
                        }

                        $violationName = $record->violation->violation_name ?: 'Unnamed Violation #' . $record->violation->id;

                        if (($violationName === 'Others' || $violationName === 'Other') && $record->other_violation) {
                            return $violationName . ': ' . $record->other_violation;
                        }

                        return $violationName;
                    }),
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
                Tables\Columns\TextColumn::make('form_count')
                    ->label('Form Count')
                    ->getStateUsing(fn (YellowForm $record): int => $record->getFormCountAttribute())
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 3 => 'danger',
                        $state == 2 => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'department_name'),
                Tables\Filters\SelectFilter::make('violation')
                    ->relationship('violation', 'violation_name'),
                Tables\Filters\Filter::make('pending_verification')
                    ->label('Pending Verification')
                    ->query(fn (Builder $query): Builder => $query->where('complied', false))
                    ->toggle(),
                Tables\Filters\Filter::make('pending_dean_verification')
                    ->label('Pending Dean Verification')
                    ->query(fn (Builder $query): Builder => $query->where('dean_verification', false))
                    ->toggle(),
                Tables\Filters\Filter::make('pending_head_approval')
                    ->label('Pending Head Approval')
                    ->query(fn (Builder $query): Builder => $query->where('head_approval', false))
                    ->toggle(),
                Tables\Filters\Filter::make('repeat_offenders')
                    ->label('Repeat Offenders')
                    ->query(fn (Builder $query): Builder => $query->repeatOffenders())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (YellowForm $record): void {
                        $record->update([
                            'head_approval' => true,
                            'head_approval_date' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (YellowForm $record): bool =>
                        $record->complied &&
                        $record->dean_verification &&
                        !$record->head_approval
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_verify_compliance')
                        ->label('Mark as Complied')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'complied' => true,
                                    'compliance_date' => now(),
                                ]);
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_verify_dean')
                        ->label('Mark as Dean Verified')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'dean_verification' => true,
                                ]);
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_head_approval')
                        ->label('Approve as Head')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'head_approval' => true,
                                ]);
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->hasRole('head'))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListViolationVerifications::route('/'),
            'create' => Pages\CreateViolationVerification::route('/create'),
            'view' => Pages\ViewViolationVerification::route('/{record}'),
            'edit' => Pages\EditViolationVerification::route('/{record}/edit'),
        ];
    }
}
