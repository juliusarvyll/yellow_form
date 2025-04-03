<div>
    @php
        $record = $getRecord();
        $previousCount = $record->getPreviousFormCountAttribute();
        $previousForms = $record->getPreviousFormsAttribute();
    @endphp

    <div class="space-y-2">
        <div class="flex items-center gap-2">
            <span class="text-lg font-medium">
                {{ $previousCount }} previous violations
            </span>

            @if ($previousCount > 0)
                <span class="inline-flex items-center justify-center rounded-full bg-{{ $previousCount >= 3 ? 'danger' : ($previousCount == 2 ? 'warning' : 'primary') }}-500 px-2 py-0.5 text-xs font-medium text-white">
                    {{ $previousCount >= 3 ? 'High Risk' : ($previousCount == 2 ? 'Warning' : 'Low Risk') }}
                </span>
            @endif
        </div>

        @if ($previousCount > 0)
            <div class="border rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violation</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Complied</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($previousForms as $form)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $form->date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $form->violation->violation_legend }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full {{ $form->complied ? 'bg-green-100' : 'bg-red-100' }}">
                                        <span class="{{ $form->complied ? 'text-green-600' : 'text-red-600' }}">
                                            @if ($form->complied)
                                                ✓
                                            @else
                                                ✗
                                            @endif
                                        </span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No previous violations found.</p>
        @endif
    </div>
</div>
