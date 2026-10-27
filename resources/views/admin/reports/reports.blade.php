@extends('layouts.admin.layout')

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Reports</h1>
    </div>
    <p class="text-sm mb-4" style="color:var(--crest-muted)">
        {{ optional($overview['term'])->name ?? 'This term' }}
        @if($overview['year']) · {{ $overview['year']->name }} @endif
        · by class
    </p>

    <form method="GET" action="{{ url('/admin/reports') }}" class="ob-filters">
        <div>
            <label>Class</label>
            <select name="class_id" onchange="this.form.submit()">
                <option value="">All classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->StandardSection }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button class="crest-btn" type="submit">Show</button></noscript>
    </form>

    @if(!$overview['term'])
        <p class="bg-white border p-4">No current term is set.</p>
    @else
        @forelse($resultsByClass as $className => $rows)
            <div class="ob-table-wrap mb-6">
                <h2 class="font-medium px-4 pt-4 pb-2">{{ $className }} · results</h2>
                <table class="crest-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Average</th>
                            <th>Position</th>
                            <th>Attendance</th>
                            <th>Missing marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['student']->name }}</td>
                                <td>{{ $row['average'] ?? '—' }}{{ $row['grade'] ? ' · '.$row['grade'] : '' }}</td>
                                <td>
                                    @if($row['position'])
                                        {{ $row['position'] }} / {{ $row['class_size'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row['attendance'] !== null ? $row['attendance'].'%' : '—' }}</td>
                                <td>{{ $row['missing'] > 0 ? $row['missing'].' subject'.($row['missing'] === 1 ? '' : 's') : 'Complete' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="bg-white border p-4 mb-6">No learners yet.</p>
        @endforelse
    @endif

    @forelse($studentsByClass as $className => $group)
        <div class="ob-table-wrap mb-6">
            <h2 class="font-medium px-4 pt-4 pb-2">{{ $className }} · {{ optional(Auth::user()->school)->learnerWord() }}</h2>
            <table class="crest-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Phone</th></tr>
                </thead>
                <tbody>
                    @foreach($group as $student)
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->email }}</td>
                            <td>{{ $student->mobile_no ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="bg-white border p-4 mb-6">No {{ strtolower(optional(Auth::user()->school)->learnerWord() ?? 'students') }} yet.</p>
    @endforelse

    <div class="ob-table-wrap">
        <h2 class="font-medium px-4 pt-4 pb-2">{{ optional(Auth::user()->school)->staffWord() }}</h2>
        <table class="crest-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th></tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $teacher->email }}</td>
                        <td>{{ $teacher->mobile_no ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">None yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
