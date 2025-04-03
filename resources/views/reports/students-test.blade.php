<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .subtitle {
            font-size: 14px;
            margin-bottom: 5px;
            font-style: italic;
        }
        h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-top: 20px;
        }
        .violations {
            color: #ff0000;
            font-weight: bold;
        }
        ul {
            margin: 5px 0;
            padding-left: 15px;
        }
        li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        @foreach($subtitle as $line)
            <div class="subtitle">{{ $line }}</div>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Number</th>
                <th>Name</th>
                <th>Department</th>
                <th>Course</th>
                <th>Year</th>
                <th>Violations</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->id_number }}</td>
                    <td>{{ trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name) }}</td>
                    <td>{{ $student->department->department_name ?? 'N/A' }}</td>
                    <td>{{ $student->course->course_name ?? 'N/A' }}</td>
                    <td>{{ $student->year }}</td>
                    <td class="{{ $student->yellowForms && $student->yellowForms->count() > 0 ? 'violations' : '' }}">
                        <strong>{{ $student->yellowForms ? $student->yellowForms->count() : 0 }}</strong>
                        @if($student->yellowForms && $student->yellowForms->count() > 0)
                            <ul>
                                @foreach($student->yellowForms->sortByDesc('date')->take(3) as $form)
                                    <li>
                                        @if(isset($form->date))
                                            @if($form->date instanceof \DateTime || $form->date instanceof \Carbon\Carbon)
                                                {{ $form->date->format('m/d/Y') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($form->date)->format('m/d/Y') }}
                                            @endif
                                        @else
                                            Unknown Date
                                        @endif
                                        -
                                        @if(isset($form->violation))
                                            {{ $form->violation->violation_name ?? 'N/A' }}
                                            @if(($form->violation->violation_name === 'Others' || $form->violation->violation_name === 'Other') && $form->other_violation)
                                                : {{ $form->other_violation }}
                                            @endif
                                        @else
                                            No Violation Record
                                        @endif
                                    </li>
                                @endforeach
                                @if($student->yellowForms->count() > 3)
                                    <li><em>... and {{ $student->yellowForms->count() - 3 }} more</em></li>
                                @endif
                            </ul>
                        @else
                            <em>No violations</em>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">No students found matching the criteria</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Report generated at: {{ $generatedAt }}
    </div>
</body>
</html>
