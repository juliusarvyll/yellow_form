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
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Collection;

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
                })
                ->placeholder('None'),
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
                ->getStateUsing(fn (?YellowForm $record): int => $record ? $record->getFormCountAttribute() : 0)
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
                ->toggleable(isToggledHiddenByDefault: true),
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
            Tables\Columns\TextColumn::make('suspension_start_date')
                ->label('Suspension Start')
                ->date()
                ->sortable()
                ->visible(fn (?YellowForm $record): bool => $record ? $record->is_suspended : false),
            Tables\Columns\TextColumn::make('suspension_end_date')
                ->label('Suspension End')
                ->date()
                ->sortable()
                ->visible(fn (?YellowForm $record): bool => $record ? $record->is_suspended : false),
        );

        return $form
            ->schema([
                Forms\Components\Section::make('Find Existing Student')
                    ->schema([
                        Forms\Components\Select::make('student_search')
                            ->label('Search by ID or Name')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) use ($hasNameColumns) {
                                // First search in Students table
                                $students = Student::where(function ($query) use ($search) {
                                    $query->where('id_number', 'like', "%{$search}%")
                                        ->orWhere('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                })
                                ->limit(10)
                                ->get();

                                $studentResults = [];
                                foreach ($students as $student) {
                                    $fullName = trim("{$student->first_name} {$student->middle_name} {$student->last_name}");
                                    $studentResults["student_{$student->id}"] = "{$fullName} ({$student->id_number})";
                                }

                                // Then search in YellowForm records
                                $yellowForms = YellowForm::where(function ($query) use ($search, $hasNameColumns) {
                                    $query->where('id_number', 'like', "%{$search}%");

                                    if ($hasNameColumns) {
                                        $query->orWhere('first_name', 'like', "%{$search}%")
                                            ->orWhere('last_name', 'like', "%{$search}%");
                                    } else {
                                        $query->orWhere('name', 'like', "%{$search}%");
                                    }
                                })
                                ->limit(10)
                                ->get();

                                $yellowFormResults = [];
                                foreach ($yellowForms as $form) {
                                    if ($hasNameColumns) {
                                        $fullName = trim("{$form->first_name} {$form->middle_name} {$form->last_name}");
                                    } else {
                                        $fullName = $form->name;
                                    }
                                    $yellowFormResults["form_{$form->id}"] = "{$fullName} ({$form->id_number})";
                                }

                                // Combine and return results
                                return array_merge($studentResults, $yellowFormResults);
                            })
                            ->getOptionLabelUsing(function ($value) use ($hasNameColumns): string {
                                if (str_starts_with($value, 'student_')) {
                                    $studentId = substr($value, 8);
                                    $student = Student::find($studentId);
                                    if ($student) {
                                        $fullName = trim("{$student->first_name} {$student->middle_name} {$student->last_name}");
                                        return "{$fullName} ({$student->id_number})";
                                    }
                                } else if (str_starts_with($value, 'form_')) {
                                    $formId = substr($value, 5);
                                    $form = YellowForm::find($formId);
                                    if ($form) {
                                        if ($hasNameColumns) {
                                            $fullName = trim("{$form->first_name} {$form->middle_name} {$form->last_name}");
                                        } else {
                                            $fullName = $form->name;
                                        }
                                        return "{$fullName} ({$form->id_number})";
                                    }
                                }
                                return 'Unknown';
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) use ($hasNameColumns) {
                                if (!$state) return;

                                if (str_starts_with($state, 'student_')) {
                                    $studentId = substr($state, 8);
                                    $student = Student::find($studentId);
                                    if (!$student) return;

                                    $set('id_number', $student->id_number);

                                    if ($hasNameColumns) {
                                        $set('first_name', $student->first_name);
                                        $set('middle_name', $student->middle_name ?? '');
                                        $set('last_name', $student->last_name);
                                    } else {
                                        $fullName = trim("{$student->first_name} {$student->middle_name} {$student->last_name}");
                                        $set('name', $fullName);
                                    }

                                    $set('department_id', $student->department_id);
                                    $set('course_id', $student->course_id);
                                    $set('year', $student->year);
                                } else if (str_starts_with($state, 'form_')) {
                                    $formId = substr($state, 5);
                                    $form = YellowForm::find($formId);
                                    if (!$form) return;

                                    $set('id_number', $form->id_number);

                                    if ($hasNameColumns) {
                                        $set('first_name', $form->first_name);
                                        $set('middle_name', $form->middle_name ?? '');
                                        $set('last_name', $form->last_name);
                                    } else {
                                        $set('name', $form->name);
                                    }

                                    $set('department_id', $form->department_id);
                                    $set('course_id', $form->course_id);
                                    $set('year', $form->year);
                                }
                            })
                            ->dehydrated(false), // Don't save this field in the database
                    ])
                    ->columns(1),

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

                Forms\Components\Section::make('Student & Faculty Status')
                    ->schema([
                        Forms\Components\TextInput::make('faculty_signature')
                            ->label('Faculty Name')
                            ->disabled(),
                        Forms\Components\Toggle::make('complied')
                            ->label('Student Complied')
                            ->default(true)
                            ->helperText('Automatically marked as complied when approved'),
                        Forms\Components\DatePicker::make('compliance_date')
                            ->default(now())
                            ->helperText('Date of compliance verification'),
                        Forms\Components\Toggle::make('is_suspended')
                            ->label('Suspended')
                            ->disabled()
                            ->helperText('Automatically set when student accumulates 3 yellow forms'),
                        Forms\Components\DatePicker::make('suspension_start_date')
                            ->label('Suspension Start Date')
                            ->disabled(),
                        Forms\Components\DatePicker::make('suspension_end_date')
                            ->label('Suspension End Date')
                            ->disabled(),
                        Forms\Components\Textarea::make('suspension_notes')
                            ->label('Suspension Notes')
                            ->disabled()
                            ->columnSpanFull(),
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
                })
                ->placeholder('None'),
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
                ->getStateUsing(fn (?YellowForm $record): int => $record ? $record->getFormCountAttribute() : 0)
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
                ->toggleable(isToggledHiddenByDefault: true),
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
            Tables\Columns\TextColumn::make('suspension_start_date')
                ->label('Suspension Start')
                ->date()
                ->sortable()
                ->visible(fn (?YellowForm $record): bool => $record ? $record->is_suspended : false),
            Tables\Columns\TextColumn::make('suspension_end_date')
                ->label('Suspension End')
                ->date()
                ->sortable()
                ->visible(fn (?YellowForm $record): bool => $record ? $record->is_suspended : false),
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
                Tables\Filters\Filter::make('currently_suspended')
                    ->label('Currently Suspended')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('is_suspended', true)
                        ->where('suspension_start_date', '<=', now())
                        ->where('suspension_end_date', '>=', now()))
                    ->toggle(),
                Tables\Filters\Filter::make('suspension_completed')
                    ->label('Suspension Completed')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('is_suspended', true)
                        ->where('suspension_end_date', '<', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('impose_suspension')
                    ->label('Impose Suspension')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->form([
                        Forms\Components\DatePicker::make('suspension_start_date')
                            ->label('Suspension Start Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('suspension_end_date')
                            ->label('Suspension End Date')
                            ->default(now()->addDays(7))
                            ->required(),
                        Forms\Components\Textarea::make('suspension_notes')
                            ->label('Suspension Notes')
                            ->required(),
                    ])
                    ->action(function (YellowForm $record, array $data): void {
                        $record->update([
                            'is_suspended' => true,
                            'suspension_start_date' => $data['suspension_start_date'],
                            'suspension_end_date' => $data['suspension_end_date'],
                            'suspension_notes' => $data['suspension_notes'],
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Student Suspended')
                            ->body("Student {$record->id_number} has been suspended until {$data['suspension_end_date']}.")
                            ->send();
                    })
                    ->visible(fn (YellowForm $record): bool =>
                        !$record->isCurrentlySuspended() &&
                        auth()->user()->hasRole(['Super Admin', 'Admin', 'Dean'])),
                Tables\Actions\Action::make('lift_suspension')
                    ->label('Lift Suspension')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (YellowForm $record): void {
                        $record->update([
                            'is_suspended' => false,
                            'suspension_end_date' => now(),
                            'suspension_notes' => $record->suspension_notes . "\nSuspension lifted early on " . now()->format('Y-m-d'),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Suspension Lifted')
                            ->body("Suspension has been lifted for student {$record->id_number}.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (YellowForm $record): bool =>
                        $record->isCurrentlySuspended() &&
                        auth()->user()->hasRole(['Super Admin', 'Admin', 'Dean'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->form([
                            Forms\Components\DatePicker::make('suspension_start_date')
                                ->label('Suspension Start Date')
                                ->default(now())
                                ->required(),
                            Forms\Components\DatePicker::make('suspension_end_date')
                                ->label('Suspension End Date')
                                ->default(now()->addDays(7))
                                ->required(),
                            Forms\Components\Textarea::make('suspension_notes')
                                ->label('Suspension Notes')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (YellowForm $record) use ($data) {
                                if (!$record->isCurrentlySuspended()) {
                                    $record->update([
                                        'is_suspended' => true,
                                        'suspension_start_date' => $data['suspension_start_date'],
                                        'suspension_end_date' => $data['suspension_end_date'],
                                        'suspension_notes' => $data['suspension_notes'],
                                    ]);
                                }
                            });

                            Notification::make()
                                ->warning()
                                ->title('Students Suspended')
                                ->body("Selected students have been suspended until {$data['suspension_end_date']}.")
                                ->send();
                        })
                        ->visible(fn () => auth()->user()->hasRole(['Super Admin', 'Admin', 'Dean'])),
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
        ];
    }
}
