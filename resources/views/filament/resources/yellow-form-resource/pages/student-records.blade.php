<x-filament-panels::page>
    <x-filament-panels::form wire:submit="searchStudent">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('search')
                    ->label('Search Student')
                    ->submit()
                    ->icon('heroicon-o-magnifying-glass'),
            ]" />
    </x-filament-panels::form>

    @if($searched)
        @if($student_name)
            <div class="mt-6">
                <!-- Student summary information -->
                <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 mb-6">
                    <h2 class="text-xl font-bold">Student Information</h2>
                    <div class="flex flex-col md:flex-row gap-4 mt-4">
                        <div class="flex-1">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Number</dt>
                                    <dd class="text-base">{{ $student_id_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                                    <dd class="text-base">{{ $student_name }}</dd>
                                </div>
                                @if($student_data)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Course</dt>
                                        <dd class="text-base">{{ $student_data->course->course_name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Year</dt>
                                        <dd class="text-base">{{ $student_data->year ?? 'N/A' }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <div class="flex-1">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Yellow Forms</dt>
                                    <dd>
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $total_forms >= 3 ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' : ($total_forms >= 2 ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20' : 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20') }}">
                                            {{ $total_forms }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Status</dt>
                                    <dd>
                                        @if($is_suspended)
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Suspended</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                        @endif
                                    </dd>
                                </div>
                                @if($is_suspended && $suspension_info)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspension Period</dt>
                                        <dd class="text-base">{{ \Carbon\Carbon::parse($suspension_info['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($suspension_info['end_date'])->format('M d, Y') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if($is_suspended && $suspension_info)
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspension Notes</h3>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $suspension_info['notes'] }}</p>
                        </div>
                    @endif
                </div>

                <!-- Yellow Forms Section -->
                <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">All Yellow Forms for this Student</h2>
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $total_forms >= 3 ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' : ($total_forms >= 2 ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20' : 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20') }}">
                            {{ $total_forms }} form(s)
                        </span>
                    </div>

                    @if($total_forms == 0)
                        <div class="bg-blue-50 p-4 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-800">
                                        This student has no yellow forms on record.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        {{ $this->table }}
                    @endif
                </div>
            </div>

        @else
            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            No student found with ID number <strong>{{ $student_id_number }}</strong>.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
