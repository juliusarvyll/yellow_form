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

    public static function form(Form $form): Form
    {
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
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'graduated' => 'Graduated',
                                'transferred' => 'Transferred',
                                'suspended' => 'Suspended',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
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
                    ->relationship('department', 'department_name'),
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'course_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'graduated' => 'Graduated',
                        'transferred' => 'Transferred',
                        'suspended' => 'Suspended',
                    ]),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Forms\Components\Select::make('department_id')
                            ->label('Department (Optional)')
                            ->options(function () {
                                return Department::pluck('department_name', 'id')
                                    ->filter()
                                    ->toArray();
                            })
                            ->searchable()
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
                        Forms\Components\Select::make('status')
                            ->label('Status (Optional)')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'graduated' => 'Graduated',
                                'transferred' => 'Transferred',
                                'suspended' => 'Suspended',
                            ]),
                        Forms\Components\Radio::make('include_violations')
                            ->label('Include Violations')
                            ->options([
                                'all' => 'All Students',
                                'with_violations' => 'Only Students with Violations',
                                'without_violations' => 'Only Students without Violations',
                            ])
                            ->default('all'),
                    ])
                    ->action(function (array $data) {
                        $query = Student::query()
                            ->with([
                                'department',
                                'course',
                                'yellowForms',
                                'yellowForms.violation'
                            ]);

                        // Apply filters
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

                        // Get students data
                        $students = $query->get();

                        // Generate title based on filters
                        $title = 'Student Report';
                        $subtitle = [];

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

                        // Generate PDF
                        return self::generatePdf($students, $title, $subtitle);
                    }),
                Tables\Actions\Action::make('import')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
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
