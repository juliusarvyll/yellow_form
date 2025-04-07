<div>
    <h2 class="text-xl font-bold">Debug Information</h2>

    <div class="mt-4">
        <h3 class="text-lg font-medium">Current Record</h3>
        <div class="grid grid-cols-2 gap-4 mt-2">
            <div>
                <span class="font-medium">ID:</span>
                <span>{{ $record->id }}</span>
            </div>
            <div>
                <span class="font-medium">Student ID Number:</span>
                <span>"{{ $studentIdNumber }}"</span>
            </div>
            <div>
                <span class="font-medium">Date:</span>
                <span>{{ $record->date }}</span>
            </div>
            <div>
                <span class="font-medium">Form Count Method:</span>
                <span>{{ $formCount }}</span>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h3 class="text-lg font-medium">All Forms with Same ID Number</h3>
        <div class="overflow-x-auto">
            <table class="w-full mt-2 border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border">Form ID</th>
                        <th class="p-2 border">ID Number</th>
                        <th class="p-2 border">Date</th>
                        <th class="p-2 border">Violation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allForms as $form)
                        <tr>
                            <td class="p-2 border">{{ $form->id }}</td>
                            <td class="p-2 border">"{{ $form->id_number }}"</td>
                            <td class="p-2 border">{{ $form->date }}</td>
                            <td class="p-2 border">
                                @if($form->violation)
                                    {{ $form->violation->violation_name }}
                                @else
                                    No Violation
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-2 text-center border">No forms found with ID number: "{{ $studentIdNumber }}"</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
