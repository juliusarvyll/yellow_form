<?php

namespace App\Filament\Resources\Dean;

use App\Filament\Resources\Dean\YellowFormResource\Pages;
use App\Models\YellowForm;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Student;

class YellowFormResource extends Resource
{
    protected static ?string $model = YellowForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Yellow Forms';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Student Violation';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        // For debugging
        $user = Auth::user();
        $departmentId = $user ? $user->department_id : null;

        // Log debugging info
        \Log::info("Dean Auth User: ", [
            'user_exists' => (bool)$user,
            'department_id' => $departmentId,
        ]);

        $query = parent::getEloquentQuery();

        // Count total forms before filtering
        $totalForms = $query->count();
        \Log::info("Total forms before filtering: $totalForms");

        if ($departmentId) {
            $query->where('department_id', $departmentId);
            $deptFilteredCount = $query->count();
            \Log::info("Forms after dept filtering: $deptFilteredCount");
        }

        $verificationFilteredCount = $query->count();
        \Log::info("Forms after filtering: $verificationFilteredCount");

        return $query->orderBy('date', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('id_number')
                            ->label('Student ID Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('student_name')
                            ->label('Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if (!$record) return;

                                $student = Student::where('id_number', $record->id_number)->first();
                                if ($student) {
                                    $component->state(trim("{$student->first_name} {$student->middle_name} {$student->last_name}"));
                                } else {
                                    $component->state(trim("{$record->first_name} {$record->middle_name} {$record->last_name}"));
                                }
                            }),
                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'department_name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'course_name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\TextInput::make('year')
                            ->disabled(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Violation Date')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Violation Details')
                    ->schema([
                        Forms\Components\Select::make('violation_id')
                            ->relationship('violation', 'violation_name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\TextInput::make('other_violation')
                            ->label('Violation Description')
                            ->disabled()
                            ->visible(fn (YellowForm $record): bool =>
                                $record->violation && $record->violation->violation_name === 'Others'),
                        Forms\Components\ViewField::make('form_count')
                            ->label('Previous Violations')
                            ->view('filament.forms.components.violation-history'),
                    ]),

                Forms\Components\Section::make('Student & Faculty Status')
                    ->schema([
                        Forms\Components\TextInput::make('faculty_signature')
                            ->label('Faculty Name')
                            ->disabled(),
                        Forms\Components\Toggle::make('complied')
                            ->label('Student Complied')
                            ->helperText('Mark if the student has complied with requirements'),
                        Forms\Components\DatePicker::make('compliance_date')
                            ->helperText('Date when compliance was confirmed'),
                    ])->columns(2),

                Forms\Components\Section::make('Dean Verification')
                    ->schema([
                        Forms\Components\Toggle::make('dean_verification')
                            ->label('I verify this violation report')
                            ->required()
                            ->helperText('By verifying, you confirm this violation has been properly documented and processed'),
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->placeholder('Add any notes regarding the verification process')
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(function (YellowForm $record): string {
                        $student = Student::where('id_number', $record->id_number)->first();
                        if ($student) {
                            return trim("{$student->first_name} {$student->middle_name} {$student->last_name}");
                        }
                        return trim("{$record->first_name} {$record->middle_name} {$record->last_name}");
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            Student::select('last_name')
                                ->whereColumn('students.id_number', 'yellow_forms.id_number')
                                ->limit(1),
                            $direction
                        )->orderBy(
                            Student::select('first_name')
                                ->whereColumn('students.id_number', 'yellow_forms.id_number')
                                ->limit(1),
                            $direction
                        );
                    }),
                Tables\Columns\TextColumn::make('course.course_name')
                    ->label('Course')
                    ->searchable(),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('violation_description')
                    ->label('Violation')
                    ->getStateUsing(function (YellowForm $record): string {
                        if ($record->violation && $record->violation->violation_name === 'OTHER') {
                            return 'OTHER: ' . ($record->other_violation ?? 'Not specified');
                        }
                        return $record->violation ? $record->violation->violation_name : 'Unknown';
                    })
                    ->searchable(['violation.violation_legend', 'other_violation']),
                Tables\Columns\IconColumn::make('complied')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('complied')
                    ->query(fn (Builder $query): Builder => $query->where('complied', true))
                    ->toggle(),
                Tables\Filters\Filter::make('not_complied')
                    ->query(fn (Builder $query): Builder => $query->where('complied', false))
                    ->toggle(),
                Tables\Filters\Filter::make('repeat_offenders')
                    ->label('Repeat Offenders')
                    ->query(fn (Builder $query): Builder => $query->repeatOffenders())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->update([
                                'verification_notes' => 'Reviewed by Dean: ' . Auth::user()->name . ' on ' . now()->format('Y-m-d H:i'),
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListYellowForms::route('/'),
            'view' => Pages\ViewYellowForm::route('/{record}'),
            'edit' => Pages\EditYellowForm::route('/{record}/edit'),
        ];
    }
}
