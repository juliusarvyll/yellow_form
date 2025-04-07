<?php

namespace App\Filament\Resources\YellowFormResource\Pages;

use App\Filament\Resources\YellowFormResource;
use App\Models\YellowForm;
use App\Models\Student;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StudentRecords extends Page
{
    protected static string $resource = YellowFormResource::class;

    protected static string $view = 'filament.resources.yellow-form-resource.pages.student-records';

    public ?string $student_id_number = null;
    public ?string $student_name = null;
    public $student_data = null;
    public $records = null;
    public $total_forms = 0;
    public $is_suspended = false;
    public $suspension_info = null;
    public $searched = false;

    public function mount(): void
    {
        // Fill the form with the query parameter if it exists
        if (request()->has('student_id_number')) {
            $this->student_id_number = request()->get('student_id_number');
            $this->form->fill([
                'student_id_number' => $this->student_id_number,
            ]);

            // Automatically search if ID is provided
            $this->searchStudent();
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student_id_number')
                    ->label('Student ID Number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function searchStudent(): void
    {
        $this->searched = true;
        $this->validate([
            'student_id_number' => 'required',
        ]);

        $user = Auth::user();
        $username = $user ? $user->name : 'Unknown User';
        $userId = $user ? $user->id : 'N/A';

        Log::info("Student search initiated", [
            'user_id' => $userId,
            'username' => $username,
            'student_id' => $this->student_id_number,
            'timestamp' => now()->toDateTimeString(),
            'ip_address' => request()->ip(),
        ]);

        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        try {
            $student = Student::where('id_number', $this->student_id_number)->first();
            if ($student) {
                $this->student_name = trim("{$student->first_name} {$student->middle_name} {$student->last_name}");
                $this->student_data = $student;

                Log::info("Student found in Students table", [
                    'student_id' => $this->student_id_number,
                    'student_name' => $this->student_name,
                    'user_id' => $userId
                ]);
            } else {
                Log::info("Student not found in Students table", [
                    'student_id' => $this->student_id_number,
                    'user_id' => $userId
                ]);
            }

            $this->records = YellowForm::where('id_number', $this->student_id_number)
                ->orderBy('date', 'desc')
                ->get();

            Log::info("Yellow forms query executed", [
                'student_id' => $this->student_id_number,
                'forms_found' => $this->records->count(),
                'user_id' => $userId
            ]);

            if (!$student && $this->records->count() > 0) {
                $firstForm = $this->records->first();
                if ($hasNameColumns) {
                    $this->student_name = trim("{$firstForm->first_name} {$firstForm->middle_name} {$firstForm->last_name}");
                } else {
                    $this->student_name = $firstForm->name;
                }

                Log::info("Student found in Yellow Forms only", [
                    'student_id' => $this->student_id_number,
                    'student_name' => $this->student_name,
                    'user_id' => $userId
                ]);
            }

            $this->total_forms = $this->records->count();

            $currentlySuspended = $this->records->first(function ($form) {
                return $form->isCurrentlySuspended();
            });

            if ($currentlySuspended) {
                $this->is_suspended = true;
                $this->suspension_info = [
                    'start_date' => $currentlySuspended->suspension_start_date,
                    'end_date' => $currentlySuspended->suspension_end_date,
                    'notes' => $currentlySuspended->suspension_notes,
                ];

                Log::info("Student is currently suspended", [
                    'student_id' => $this->student_id_number,
                    'suspension_start' => $currentlySuspended->suspension_start_date,
                    'suspension_end' => $currentlySuspended->suspension_end_date,
                    'user_id' => $userId
                ]);
            }

            Log::info("Student search results summary", [
                'student_id' => $this->student_id_number,
                'found' => $this->student_name ? true : false,
                'forms_count' => $this->total_forms,
                'is_suspended' => $this->is_suspended,
                'user_id' => $userId,
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error during student search", [
                'student_id' => $this->student_id_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);

            throw $e;
        }
    }

    public function table(Table $table): Table
    {
        $hasNameColumns = Schema::hasColumns('yellow_forms', ['first_name', 'middle_name', 'last_name']);

        return $table
            ->query(YellowForm::where('id_number', $this->student_id_number))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('violation.violation_name')
                    ->label('Violation')
                    ->getStateUsing(function (?YellowForm $record): string {
                        if (!$record || !$record->violation) {
                            return 'No Violation';
                        }

                        $violationName = $record->violation->violation_name ?: 'Unnamed Violation #' . $record->violation->id;

                        if (($violationName === 'Others' || $violationName === 'Other') && $record->other_violation) {
                            return $violationName . ': ' . $record->other_violation;
                        }

                        return $violationName;
                    }),
                Tables\Columns\IconColumn::make('complied')
                    ->boolean(),
                Tables\Columns\IconColumn::make('dean_verification')
                    ->label('Dean Verified')
                    ->boolean(),
                Tables\Columns\IconColumn::make('head_approval')
                    ->label('Head Approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('faculty_signature')
                    ->label('Reported By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Reported On'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->before(function (YellowForm $record) {
                        $user = Auth::user();
                        $username = $user ? $user->name : 'Unknown User';
                        $userId = $user ? $user->id : 'N/A';

                        Log::info("Yellow form viewed", [
                            'user_id' => $userId,
                            'username' => $username,
                            'student_id' => $record->id_number,
                            'form_id' => $record->id,
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                    }),
                Tables\Actions\EditAction::make()
                    ->before(function (YellowForm $record) {
                        $user = Auth::user();
                        $username = $user ? $user->name : 'Unknown User';
                        $userId = $user ? $user->id : 'N/A';

                        Log::info("Yellow form edit initiated", [
                            'user_id' => $userId,
                            'username' => $username,
                            'student_id' => $record->id_number,
                            'form_id' => $record->id,
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                    }),
            ])
            ->emptyStateHeading('No yellow forms found')
            ->emptyStateDescription('No disciplinary records found for this student ID.');
    }
}
