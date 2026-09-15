@extends('layouts.teacher.layout')

@section('content')
<div class="">
    <h1 class="admin-h1 my-3">Results · {{ $class->StandardSection }}</h1>
    @include('partials.message')
    <p class="text-sm text-gray-600 mb-4">
        {{ $term->name }} · CA / {{ $settings->ca_max }} · Mid-term / {{ $settings->midterm_max }} · Exam / {{ $settings->exam_max }} · WAEC A1–F9
    </p>

    <form method="GET" class="mb-4">
        <label class="text-sm mr-2">Subject</label>
        <select name="subject_id" class="border px-2 py-1" onchange="this.form.submit()">
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" @selected($subject->id == $subjectId)>{{ $subject->name }}</option>
            @endforeach
        </select>
    </form>

    @if($subjectId)
    <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/results') }}" class="bg-white border p-4 mb-6">
        @csrf
        <input type="hidden" name="subject_id" value="{{ $subjectId }}">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Student</th>
                    <th>CA</th>
                    <th>Mid-term</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $row)
                    @php $score = $scores->get($row->user_id); @endphp
                    <tr class="border-b">
                        <td class="py-2">{{ $row->user->name }}</td>
                        <td><input type="number" step="0.5" min="0" max="{{ $settings->ca_max }}" name="rows[{{ $row->user_id }}][ca]" value="{{ $score->ca ?? '' }}" class="border w-20 px-1 py-1"></td>
                        <td><input type="number" step="0.5" min="0" max="{{ $settings->midterm_max }}" name="rows[{{ $row->user_id }}][midterm]" value="{{ $score->midterm ?? '' }}" class="border w-20 px-1 py-1"></td>
                        <td><input type="number" step="0.5" min="0" max="{{ $settings->exam_max }}" name="rows[{{ $row->user_id }}][exam]" value="{{ $score->exam ?? '' }}" class="border w-20 px-1 py-1"></td>
                        <td>{{ $score->total ?? '—' }}</td>
                        <td>{{ $score->grade ?? '—' }}</td>
                        <td>{{ $score->remark ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button class="mt-4 bg-gray-900 text-white text-sm px-4 py-2">Save scores</button>
    </form>
    @endif

    @if($isFormTeacher)
        <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/results/publish') }}" class="mb-6">
            @csrf
            <button class="bg-indigo-700 text-white text-sm px-4 py-2">Publish term results</button>
            <span class="text-xs text-gray-500 ml-2">Computes class positions and lets students download report cards.</span>
        </form>
    @endif

    @if($termResults->count())
        <div class="bg-white border p-4 mb-6">
            <h2 class="font-semibold mb-2">Class positions</h2>
            <table class="w-full text-sm">
                <tr class="text-left border-b"><th class="py-1">Student</th><th>Average</th><th>Position</th></tr>
                @foreach($students as $row)
                    @php $tr = $termResults->get($row->user_id); @endphp
                    <tr class="border-b">
                        <td class="py-1">{{ $row->user->name }}</td>
                        <td>{{ $tr->average ?? '—' }}</td>
                        <td>{{ $tr->position ?? '—' }}@if($tr) / {{ $tr->class_size }}@endif</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="bg-white border p-4">
        <h2 class="font-semibold mb-2">Score changes</h2>
        @forelse($audits as $audit)
            <p class="text-xs text-gray-600 py-1">{{ $audit->changer->name ?? 'Teacher' }} changed {{ $audit->field }} from {{ $audit->old_value ?? '—' }} to {{ $audit->new_value }} · {{ $audit->created_at->diffForHumans() }}</p>
        @empty
            <p class="text-sm text-gray-500">No changes logged yet.</p>
        @endforelse
    </div>
</div>
@endsection
