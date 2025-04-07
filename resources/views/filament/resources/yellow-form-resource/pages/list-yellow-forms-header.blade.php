<div class="mb-4 p-2 bg-white rounded-lg shadow dark:bg-gray-800">
    <form action="{{ \App\Filament\Resources\YellowFormResource::getStudentRecordsUrl() }}" method="get" class="flex items-center space-x-2">
        <div class="flex-1">
            <label for="student_id_number" class="sr-only">Search Student</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <input type="text" id="student_id_number" name="student_id_number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter Student ID Number" required>
            </div>
        </div>
        <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
            View Student Records
        </button>
    </form>
    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Search for a student to view all their yellow forms in one place
    </div>
</div>
