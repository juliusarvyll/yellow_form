<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YellowFormResource\Pages;
use App\Models\YellowForm;
use App\Models\Department;
use App\Models\Violation;
use App\Models\Course;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Filament\Resources\YellowFormResource\Pages\StudentRecords;

class YellowFormResource extends Resource
{
    protected static ?string $model = YellowForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // Check if yellow_forms table has the new name columns
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        // Build the student information schema based on table structure
        $studentInfoSchema = [
            Forms\Components\TextInput::make('id_number')
                ->label('Student ID Number')
                ->required()
                ->maxLength(255)
                ->live(debounce: 500)
                ->afterStateUpdated(function (string $state, callable $set) use ($hasNameColumns) {
                    // First check yellow forms
                    $existingYellowForm = YellowForm::where('id_number', $state)->first();

                    if ($existingYellowForm) {
                        if ($hasNameColumns) {
                            $set('first_name', $existingYellowForm->first_name);
                            $set('middle_name', $existingYellowForm->middle_name ?? '');
                            $set('last_name', $existingYellowForm->last_name);
                        } else {
                            $set('name', $existingYellowForm->name);
                        }

                        $set('department_id', $existingYellowForm->department_id);
                        $set('course_id', $existingYellowForm->course_id);
                        $set('year', $existingYellowForm->year);
                        return;
                    }

                    // Then check students table
                    $existingStudent = Student::where('id_number', $state)->first();

                    if ($existingStudent) {
                        if ($hasNameColumns) {
                            $set('first_name', $existingStudent->first_name);
                            $set('middle_name', $existingStudent->middle_name ?? '');
                            $set('last_name', $existingStudent->last_name);
                        } else {
                            $fullName = trim("{$existingStudent->first_name} {$existingStudent->middle_name} {$existingStudent->last_name}");
                            $set('name', $fullName);
                        }

                        $set('department_id', $existingStudent->department_id);
                        $set('course_id', $existingStudent->course_id);
                        $set('year', $existingStudent->year);
                    }
                }),

            Forms\Components\Select::make('department_id')
                ->label('Department')
                ->options(function () {
                    return Department::pluck('department_name', 'id')
                        ->filter() // Remove any null values
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('course_id', null)),
            Forms\Components\Select::make('course_id')
                ->label('Course')
                ->options(function (callable $get) {
                    $departmentId = $get('department_id');

                    if (!$departmentId) {
                        return [];
                    }

                    return Course::where('department_id', $departmentId)
                        ->pluck('course_name', 'id')
                        ->filter() // Remove any null values
                        ->toArray();
                })
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('year')
                ->required()
                ->maxLength(255),
            Forms\Components\DatePicker::make('date')
                ->required(),
        ];

        // Add name fields based on database structure
        if ($hasNameColumns) {
            // Add after the id_number field (index 0)
            array_splice($studentInfoSchema, 1, 0, [
                Forms\Components\TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('middle_name')
                    ->label('Middle Name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),
            ]);
        } else {
            // Add after the id_number field (index 0)
            array_splice($studentInfoSchema, 1, 0, [
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
        }

        // Now build the table columns
        $tableColumns = [
            Tables\Columns\TextColumn::make('id_number')
                ->searchable()
                ->sortable(),
        ];

        // Add name column based on database structure
        if ($hasNameColumns) {
            $tableColumns[] = Tables\Columns\TextColumn::make('full_name')
                ->label('Name')
                ->getStateUsing(function (YellowForm $record): string {
                    return trim("{$record->first_name} {$record->middle_name} {$record->last_name}");
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('last_name', $direction)
                        ->orderBy('first_name', $direction);
                });
        } else {
            $tableColumns[] = Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable();
        }

        // Add the rest of the common columns
        array_push($tableColumns,
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
            Tables\Columns\TextColumn::make('date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('violation.violation_name')
                ->label('Violation')
                ->sortable()
                ->getStateUsing(function (YellowForm $record): string {
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
                })
                ->placeholder('None'),
            Tables\Columns\IconColumn::make('student_approval')
                ->boolean()
                ->sortable(),
            Tables\Columns\IconColumn::make('complied')
                ->boolean()
                ->sortable(),
            Tables\Columns\IconColumn::make('dean_verification')
                ->label('Dean Verified')
                ->boolean()
                ->sortable(),
            Tables\Columns\TextColumn::make('form_count')
                ->label('Form Count')
                ->getStateUsing(fn (YellowForm $record): int => $record->getFormCountAttribute())
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->selectRaw('yellow_forms.*, (SELECT COUNT(*) FROM yellow_forms as yf WHERE yf.id_number = yellow_forms.id_number) as form_count')
                        ->orderBy('form_count', $direction);
                })
                ->badge()
                ->color(fn (int $state): string => match(true) {
                    $state >= 3 => 'danger',
                    $state == 2 => 'warning',
                    default => 'success',
                }),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
        );

        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema($studentInfoSchema)
                    ->columns(2),

                Forms\Components\Section::make('Violation Details')
                    ->schema([
                        Forms\Components\Select::make('violation_id')
                            ->label('Violation')
                            ->options(function () {
                                $violations = Violation::all();
                                $options = [];

                                foreach ($violations as $violation) {
                                    // Ensure we never have null values as option labels
                                    $violationName = $violation->violation_name;
                                    if ($violationName === null || $violationName === '') {
                                        $violationName = 'Violation #' . $violation->id;
                                    }

                                    $options[$violation->id] = $violationName;
                                }

                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('other_violation')
                            ->label('Specify Other Violation')
                            ->maxLength(255)
                            ->required(function (callable $get) {
                                $violationId = $get('violation_id');
                                if (!$violationId) return false;

                                // Check if the selected violation is "Others"
                                // Replace '999' with the actual ID of your "Others" violation
                                $othersViolationId = Violation::where('violation_name', 'Others')
                                    ->orWhere('violation_name', 'Other')
                                    ->value('id');

                                return $violationId == $othersViolationId;
                            })
                            ->visible(function (callable $get) {
                                $violationId = $get('violation_id');
                                if (!$violationId) return false;

                                // Check if the selected violation is "Others"
                                // Replace '999' with the actual ID of your "Others" violation
                                $othersViolationId = Violation::where('violation_name', 'Others')
                                    ->orWhere('violation_name', 'Other')
                                    ->value('id');

                                return $violationId == $othersViolationId;
                            }),
                    ])->columns(1),

                Forms\Components\Section::make('Status and Approvals')
                    ->schema([
                        Forms\Components\Toggle::make('student_approval')
                            ->label('Student Approval')
                            ->default(false),
                        Forms\Components\TextInput::make('faculty_signature')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('complied')
                            ->label('Student Complied')
                            ->default(false),
                        Forms\Components\DatePicker::make('compliance_date'),
                        Forms\Components\Toggle::make('dean_verification')
                            ->label('Verified by Dean')
                            ->default(false),
                        Forms\Components\TextInput::make('noted_by')
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Check if yellow_forms table has the new name columns
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        // Build table columns
        $tableColumns = [
            Tables\Columns\TextColumn::make('id_number')
                ->searchable()
                ->sortable(),
        ];

        // Add name column based on database structure
        if ($hasNameColumns) {
            $tableColumns[] = Tables\Columns\TextColumn::make('full_name')
                ->label('Name')
                ->getStateUsing(function (YellowForm $record): string {
                    return trim("{$record->first_name} {$record->middle_name} {$record->last_name}");
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('last_name', $direction)
                        ->orderBy('first_name', $direction);
                });
        } else {
            $tableColumns[] = Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable();
        }

        // Add the rest of the columns
        array_push($tableColumns,
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
            Tables\Columns\TextColumn::make('date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('violation.violation_name')
                ->label('Violation')
                ->sortable()
                ->getStateUsing(function (YellowForm $record): string {
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
                })
                ->placeholder('None'),
            Tables\Columns\IconColumn::make('student_approval')
                ->boolean()
                ->sortable(),
            Tables\Columns\IconColumn::make('complied')
                ->boolean()
                ->sortable(),
            Tables\Columns\IconColumn::make('dean_verification')
                ->label('Dean Verified')
                ->boolean()
                ->sortable(),
            Tables\Columns\TextColumn::make('form_count')
                ->label('Form Count')
                ->getStateUsing(fn (YellowForm $record): int => $record->getFormCountAttribute())
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->selectRaw('yellow_forms.*, (SELECT COUNT(*) FROM yellow_forms as yf WHERE yf.id_number = yellow_forms.id_number) as form_count')
                        ->orderBy('form_count', $direction);
                })
                ->badge()
                ->color(fn (int $state): string => match(true) {
                    $state >= 3 => 'danger',
                    $state == 2 => 'warning',
                    default => 'success',
                }),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
        );

        return $table
            ->columns($tableColumns)
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'department_name'),
                Tables\Filters\SelectFilter::make('violation')
                    ->relationship('violation', 'violation_name'),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListYellowForms::route('/'),
            'create' => Pages\CreateYellowForm::route('/create'),
            'view' => Pages\ViewYellowForm::route('/{record}'),
            'edit' => Pages\EditYellowForm::route('/{record}/edit'),
            'student-records' => Pages\StudentRecords::route('/student-records'),
        ];
    }
}
