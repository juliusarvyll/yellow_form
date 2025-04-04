<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use App\Models\Department;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Actions\ImportAction;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use League\Csv\Reader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Filament\Notifications\Notification;
use League\Csv\Writer;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 2;

    /**
     * Customize the navigation group based on the panel context
     */
    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        // For dean panel, use a more specific group
        if ($user && $user->hasRole('Dean')) {
            return 'Department Management';
        }

        return static::$navigationGroup;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // If the user is a dean, scope to their department
        if ($user && $user->hasRole('Dean') && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isDean = $user && $user->hasRole('Dean');
        $userDepartmentId = $user ? $user->department_id : null;

        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('id_number')
                            ->label('Student ID Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
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
                        Forms\Components\Select::make('sex')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Other' => 'Other',
                            ]),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Academic Information')
                    ->schema([
                        Forms\Components\Toggle::make('is_suspended')
                            ->label('Suspend Student')
                            ->helperText('Students with 3 or more violations can be suspended')
                            ->default(false)
                            ->visible(function (Student $record) {
                                return $record->exists && $record->getViolationCountAttribute() >= 3;
                            })
                            ->disabled(function (Student $record) {
                                return !$record->exists || $record->getViolationCountAttribute() < 3;
                            }),
                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->options(function () use ($isDean, $userDepartmentId) {
                                if ($isDean && $userDepartmentId) {
                                    // For deans, only show their department
                                    return Department::where('id', $userDepartmentId)
                                        ->pluck('department_name', 'id')
                                        ->toArray();
                                }

                                // For admins, show all departments
                                return Department::pluck('department_name', 'id')
                                    ->filter() // Remove any null values
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->disabled($isDean) // Disable for deans so they can't change it
                            ->default(function () use ($isDean, $userDepartmentId) {
                                // Set default to dean's department
                                return $isDean ? $userDepartmentId : null;
                            })
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
                            ->maxLength(255)
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isDean = $user && $user->hasRole('Dean');
        $userDepartmentId = $user ? $user->department_id : null;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(function (Student $record): string {
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
                    }),
                Tables\Columns\TextColumn::make('course.course_name')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->searchable(),
                Tables\Columns\TextColumn::make('violation_count')
                    ->label('Violations')
                    ->getStateUsing(fn (Student $record): int => $record->getViolationCountAttribute())
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 3 => 'danger',
                        $state == 2 => 'warning',
                        $state == 1 => 'info',
                        default => 'success',
                    }),
                Tables\Columns\IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'department_name')
                    ->visible(!$isDean) // Hide department filter for deans since they only see their department
                    ->preload(),
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'course_name', function ($query) use ($isDean, $userDepartmentId) {
                        // For deans, only show courses from their department
                        if ($isDean && $userDepartmentId) {
                            return $query->where('department_id', $userDepartmentId);
                        }

                        return $query;
                    })
                    ->preload(),
                Tables\Filters\Filter::make('has_violations')
                    ->query(fn (Builder $query): Builder => $query->has('yellowForms'))
                    ->label('Has Violations')
                    ->toggle(),
                Tables\Filters\Filter::make('repeat_offenders')
                    ->query(fn (Builder $query): Builder => $query->has('yellowForms', '>=', 2))
                    ->label('Repeat Offenders')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('toggle_suspension')
                        ->label(fn (Student $record): string => $record->is_suspended ? 'Unsuspend' : 'Suspend')
                        ->icon(fn (Student $record): string => $record->is_suspended ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                        ->color(fn (Student $record): string => $record->is_suspended ? 'success' : 'danger')
                        ->visible(fn (Student $record): bool => $record->getViolationCountAttribute() >= 3)
                        ->requiresConfirmation()
                        ->modalDescription(fn (Student $record): string =>
                            $record->is_suspended
                                ? "Are you sure you want to unsuspend this student?"
                                : "This student has {$record->getViolationCountAttribute()} violations. Are you sure you want to suspend them?"
                        )
                        ->action(function (Student $record): void {
                            if ($record->getViolationCountAttribute() >= 3) {
                                $record->is_suspended = !$record->is_suspended;
                                $record->save();

                                Notification::make()
                                    ->title($record->is_suspended ? 'Student Suspended' : 'Student Unsuspended')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Cannot Suspend Student')
                                    ->body('Students can only be suspended if they have 3 or more violations.')
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(!$isDean), // Only available for admins
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Forms\Components\Section::make('Date Range')
                            ->schema([
                                Forms\Components\DatePicker::make('date_from')
                                    ->label('From Date')
                                    ->maxDate(now()),
                                Forms\Components\DatePicker::make('date_to')
                                    ->label('To Date')
                                    ->default(now())
                                    ->maxDate(now()),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Filters')
                            ->schema([
                                Forms\Components\Select::make('department_id')
                                    ->label('Department (Optional)')
                                    ->options(function () use ($isDean, $userDepartmentId) {
                                        if ($isDean && $userDepartmentId) {
                                            // For deans, only show their department
                                            return Department::where('id', $userDepartmentId)
                                                ->pluck('department_name', 'id')
                                                ->toArray();
                                        }

                                        return Department::pluck('department_name', 'id')
                                            ->filter()
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->default(function () use ($isDean, $userDepartmentId) {
                                        // Set default to dean's department
                                        return $isDean ? $userDepartmentId : null;
                                    })
                                    ->disabled($isDean) // Disable for deans so they can't change it
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('course_id', null)),

                                Forms\Components\Select::make('course_id')
                                    ->label('Course (Optional)')
                                    ->options(function (callable $get) {
                                        $departmentId = $get('department_id');

                                        if (!$departmentId) {
                                            return Course::pluck('course_name', 'id')
                                                ->filter()
                                                ->toArray();
                                        }

                                        return Course::where('department_id', $departmentId)
                                            ->pluck('course_name', 'id')
                                            ->filter()
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->visible(fn (callable $get) => $get('department_id') !== null),
                            ]),

                        Forms\Components\Section::make('Approval Filters')
                            ->schema([
                                Forms\Components\Select::make('dean_approval')
                                    ->label('Dean Approval Status')
                                    ->options([
                                        'all' => 'All Students',
                                        'approved' => 'Approved by Dean',
                                        'not_approved' => 'Not Approved by Dean',
                                    ])
                                    ->default('all'),

                                Forms\Components\Select::make('head_approval')
                                    ->label('Head Approval Status')
                                    ->options([
                                        'all' => 'All Students',
                                        'approved' => 'Approved by Head',
                                        'not_approved' => 'Not Approved by Head',
                                    ])
                                    ->default('all'),

                                Forms\Components\Radio::make('include_violations')
                                    ->label('Violation Status')
                                    ->options([
                                        'all' => 'All Students',
                                        'with_violations' => 'Only Students with Violations',
                                        'without_violations' => 'Only Students without Violations',
                                    ])
                                    ->default('all'),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        $query = Student::query()
                            ->with([
                                'department',
                                'course',
                                'yellowForms',
                                'yellowForms.violation'
                            ]);

                        // Apply date range filters if provided
                        if (!empty($data['date_from'])) {
                            $query->whereHas('yellowForms', function($query) use ($data) {
                                $query->whereDate('created_at', '>=', $data['date_from']);
                            });
                        }

                        if (!empty($data['date_to'])) {
                            $query->whereHas('yellowForms', function($query) use ($data) {
                                $query->whereDate('created_at', '<=', $data['date_to']);
                            });
                        }

                        // Apply department and course filters
                        if (!empty($data['department_id'])) {
                            $query->where('department_id', $data['department_id']);
                        }

                        if (!empty($data['course_id'])) {
                            $query->where('course_id', $data['course_id']);
                        }

                        if (!empty($data['status'])) {
                            $query->where('status', $data['status']);
                        }

                        // Filter by violation status if requested
                        if ($data['include_violations'] === 'with_violations') {
                            $query->has('yellowForms');
                        } elseif ($data['include_violations'] === 'without_violations') {
                            $query->doesntHave('yellowForms');
                        }

                        // Filter by dean approval status
                        if ($data['dean_approval'] === 'approved') {
                            $query->whereHas('yellowForms', function ($query) {
                                $query->where('dean_verification', true);
                            });
                        } elseif ($data['dean_approval'] === 'not_approved') {
                            $query->whereHas('yellowForms', function ($query) {
                                $query->where('dean_verification', false);
                            });
                        }

                        // Filter by head approval status
                        if ($data['head_approval'] === 'approved') {
                            $query->whereHas('yellowForms', function ($query) {
                                $query->where('head_approval', true);
                            });
                        } elseif ($data['head_approval'] === 'not_approved') {
                            $query->whereHas('yellowForms', function ($query) {
                                $query->where('head_approval', false);
                            });
                        }

                        // Get students data
                        $students = $query->get();

                        // Generate title based on filters
                        $title = 'Student Report';
                        $subtitle = [];

                        // Add date range to subtitle if provided
                        if (!empty($data['date_from']) && !empty($data['date_to'])) {
                            $fromDate = date('M d, Y', strtotime($data['date_from']));
                            $toDate = date('M d, Y', strtotime($data['date_to']));
                            $subtitle[] = "Yellow Forms Date Range: {$fromDate} to {$toDate}";
                        } elseif (!empty($data['date_from'])) {
                            $fromDate = date('M d, Y', strtotime($data['date_from']));
                            $subtitle[] = "Yellow Forms From: {$fromDate}";
                        } elseif (!empty($data['date_to'])) {
                            $toDate = date('M d, Y', strtotime($data['date_to']));
                            $subtitle[] = "Yellow Forms Until: {$toDate}";
                        }

                        if (!empty($data['department_id'])) {
                            $department = Department::find($data['department_id']);
                            $subtitle[] = "Department: {$department->department_name}";
                        }

                        if (!empty($data['course_id'])) {
                            $course = Course::find($data['course_id']);
                            $subtitle[] = "Course: {$course->course_name}";
                        }

                        if (!empty($data['status'])) {
                            $subtitle[] = "Status: " . ucfirst($data['status']);
                        }

                        if ($data['include_violations'] === 'with_violations') {
                            $subtitle[] = "Only students with violations";
                        } elseif ($data['include_violations'] === 'without_violations') {
                            $subtitle[] = "Only students without violations";
                        }

                        if ($data['dean_approval'] === 'approved') {
                            $subtitle[] = "Approved by Dean";
                        } elseif ($data['dean_approval'] === 'not_approved') {
                            $subtitle[] = "Not approved by Dean";
                        }

                        if ($data['head_approval'] === 'approved') {
                            $subtitle[] = "Approved by Head";
                        } elseif ($data['head_approval'] === 'not_approved') {
                            $subtitle[] = "Not approved by Head";
                        }

                        // Generate PDF
                        return self::generatePdf($students, $title, $subtitle);
                    }),
                Tables\Actions\Action::make('import')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(!$isDean) // Hide import functionality for deans
                    ->form([
                        Forms\Components\FileUpload::make('csv')
                            ->label('CSV File')
                            ->disk('local')
                            ->directory('csv-imports')
                            ->acceptedFileTypes(['text/csv', 'application/csv'])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $csvPath = Storage::disk('local')->path($data['csv']);
                        $csv = Reader::createFromPath($csvPath, 'r');
                        $csv->setHeaderOffset(0);

                        $records = $csv->getRecords();

                        foreach ($records as $record) {
                            // Find course based on abbreviation
                            $course = Course::where('course_abbreviation', $record['Course'])->first();

                            if (!$course) {
                                continue; // Skip if course not found
                            }

                            // Create or update student record with separate name fields
                            Student::updateOrCreate(
                                ['id_number' => $record['Code']],
                                [
                                    'first_name' => trim($record['First Name']),
                                    'middle_name' => trim($record['Middle Name'] ?? ''),
                                    'last_name' => trim($record['Last Name']),
                                    'sex' => $record['Sex'] === 'M' ? 'Male' : 'Female',
                                    'department_id' => $course->department_id,
                                    'course_id' => $course->id,
                                    'year' => $record['Year'],
                                ]
                            );
                        }

                        // Clean up the temporary file
                        Storage::disk('local')->delete($data['csv']);

                        // Show notification
                        Notification::make()
                            ->title('CSV Import Completed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Generate PDF report of students
     *
     * @param Collection $students
     * @param string $title
     * @param array $subtitle
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected static function generatePdf(Collection $students, string $title, array $subtitle = []): \Symfony\Component\HttpFoundation\Response
    {
        try {
            // Create PDF
            $pdf = Pdf::loadView('reports.students-test', [
                'students' => $students,
                'title' => $title,
                'subtitle' => $subtitle,
                'generatedAt' => now()->format('F j, Y h:i A'),
            ]);

            // Set paper size and orientation (landscape for wider tables)
            $pdf->setPaper('a4', 'landscape');

            // Generate a filename
            $filename = 'student_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            // Return the streamDownload response
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, $filename);
        } catch (\Exception $e) {
            // Log the error with full details
            \Illuminate\Support\Facades\Log::error('PDF Generation Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            // Return an error notification
            Notification::make()
                ->title('Error Generating PDF')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->send();

            // Redirect back
            return back();
        }
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
