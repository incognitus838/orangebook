@extends('layouts.teacher.layout')

@section('content')
<div>
    <div class="ob-head">
        <h1 class="admin-h1">Results · {{ $class->StandardSection }}</h1>
    </div>
    <p class="text-sm mb-4" style="color:var(--crest-muted)">
        {{ $term->name }} · CA / {{ $settings->ca_max }} · Mid-term / {{ $settings->midterm_max }} · Exam / {{ $settings->exam_max }}@if(optional(Auth::user()->school)->usesParents()) · WAEC A1–F9 @endif
        @if($isFormTeacher) · You are the form teacher.@endif
    </p>
    @include('partials.message')

    @if($students->isEmpty())
        <p class="bg-white border p-4">No students in this class yet. Ask the principal to add them.</p>
    @else
        @if($subjects->isEmpty())
            <p class="bg-white border p-4 mb-6">No subject is assigned to you in this class. The form teacher can still publish once scores are in.</p>
        @else
            <form method="GET" class="ob-filters">
                <div>
                    <label for="ob-subject">Subject</label>
                    <select id="ob-subject" name="subject_id" onchange="this.form.submit()">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($subject->id == $subjectId)>{{ $subject->name }}</option>
                    @endforeach
                    </select>
                </div>
            </form>

            @if($subjectId)
            <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/results') }}" class="mb-6">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                <div class="ob-table-wrap">
                <table class="crest-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>CA / {{ $settings->ca_max }}</th>
                            <th>Mid-term / {{ $settings->midterm_max }}</th>
                            <th>Exam / {{ $settings->exam_max }}</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $row)
                            @php $score = $scores->get($row->user_id); @endphp
                            <tr>
                                <td>{{ $row->user->name ?? 'Student' }}</td>
                                <td><input type="number" step="0.5" min="0" max="{{ $settings->ca_max }}" name="rows[{{ $row->user_id }}][ca]" value="{{ $score->ca ?? '' }}" class="ob-score"></td>
                                <td><input type="number" step="0.5" min="0" max="{{ $settings->midterm_max }}" name="rows[{{ $row->user_id }}][midterm]" value="{{ $score->midterm ?? '' }}" class="ob-score"></td>
                                <td><input type="number" step="0.5" min="0" max="{{ $settings->exam_max }}" name="rows[{{ $row->user_id }}][exam]" value="{{ $score->exam ?? '' }}" class="ob-score"></td>
                                <td>{{ $score->total ?? '—' }}</td>
                                <td>{{ $score->grade ?? '—' }}</td>
                                <td>{{ $score->remark ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <button class="crest-btn" type="submit" style="margin-top:24px">Save scores</button>
            </form>
            @endif
        @endif

        @if($isFormTeacher)
            @if(!empty($gaps['empty_count']))
                <p class="bg-white border p-4 mb-4" style="border-color:var(--crest-mark);color:var(--crest-mark)">
                    {{ count($gaps['empty_subjects']) }} subjects empty
                    @if(!empty($gaps['empty_subjects']))
                        — {{ implode(', ', $gaps['empty_subjects']) }}
                    @endif
                    . Fill them before publish.
                </p>
            @endif
            <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/results/publish') }}" class="mb-6">
                @csrf
                <button class="crest-btn" type="submit" @disabled(!empty($gaps['empty_count']))>Publish {{ $term->name }}</button>
                <span class="text-xs" style="color:var(--crest-muted);margin-left:16px">Computes class position and opens the report card for students and parents.</span>
            </form>
        @endif

        @if($termResults->count())
            <div class="bg-white border p-4 mb-6">
                <h2>Class positions</h2>
                <table class="crest-table">
                    <thead>
                        <tr><th>Student</th><th>Average</th><th>Position</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($students as $row)
                            @php $tr = $termResults->get($row->user_id); @endphp
                            <tr>
                                <td>{{ $row->user->name ?? 'Student' }}</td>
                                <td>{{ $tr->average ?? '—' }}</td>
                                <td>
                                    @if($tr && $tr->position)
                                        {{ $tr->position }} / {{ $tr->class_size }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($tr && $tr->published_at)
                                        <a href="{{ url('/teacher/class/'.$class->id.'/results/'.$row->user_id.'/pdf') }}">PDF</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="bg-white border p-4">
            <h2>Score changes</h2>
            @forelse($audits as $audit)
                <p class="text-xs py-1" style="color:var(--crest-muted)">{{ $audit->changer->name ?? 'Teacher' }} changed {{ $audit->field }} from {{ $audit->old_value ?? '—' }} to {{ $audit->new_value }} · {{ $audit->created_at->diffForHumans() }}</p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">No changes logged yet.</p>
            @endforelse
        </div>
    @endif
</div>
@endsection
