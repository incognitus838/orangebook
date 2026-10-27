@extends('layouts.parent.layout')

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $child->name }}</h1>
        <a href="{{ url('/parent/dashboard') }}">Back to children</a>
    </div>
    <p class="text-sm mb-4" style="color:var(--crest-muted)">
        {{ $report['className'] }} · {{ optional($report['term'])->name }} {{ optional($report['year'])->name }}
        @if($report['formTeacher']) · Form teacher {{ $report['formTeacher']->name }} @endif
    </p>
    @include('partials.message')

    <div class="day-stats">
        <div class="day-stat">
            <span class="day-stat-n">{{ optional($report['termResult'])->average ?? '—' }}</span>
            <span class="day-stat-l">Average</span>
        </div>
        <div class="day-stat">
            <span class="day-stat-n">
                @if($report['termResult'] && $report['termResult']->position)
                    {{ $report['termResult']->position }}/{{ $report['termResult']->class_size }}
                @else
                    —
                @endif
            </span>
            <span class="day-stat-l">Position</span>
        </div>
        <div class="day-stat">
            <span class="day-stat-n">{{ $report['attendancePercent'] !== null ? $report['attendancePercent'].'%' : '—' }}</span>
            <span class="day-stat-l">Attendance</span>
        </div>
        <div class="day-stat">
            <span class="day-stat-n">{{ optional($report['termResult'])->grade ?? '—' }}</span>
            <span class="day-stat-l">Grade</span>
        </div>
    </div>

    <div class="ob-table-wrap mb-6">
        <h2 class="font-medium px-4 pt-4 pb-2">{{ optional($report['term'])->name ?? 'Term' }} report</h2>
        @if(!$report['termResult'] || !$report['termResult']->published_at)
            <p class="text-sm" style="color:var(--crest-muted)">The form teacher has not published this term yet.</p>
        @else
            <table class="crest-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>CA</th>
                        <th>Mid-term</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['scores'] as $score)
                        <tr>
                            <td>{{ $score->subject->name }}</td>
                            <td>{{ $score->ca }}</td>
                            <td>{{ $score->midterm }}</td>
                            <td>{{ $score->exam }}</td>
                            <td>{{ $score->total }}</td>
                            <td>{{ $score->grade }}</td>
                            <td>{{ $score->remark }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <a href="{{ url('/parent/child/'.$child->id.'/results/pdf') }}" class="crest-btn" style="margin:16px 16px 20px;display:inline-block">Download report card</a>
        @endif
    </div>

    <div class="day-lists">
        <div class="bg-white border p-4">
            <h2 class="font-medium mb-2">Homework</h2>
            @forelse($homeworks as $work)
                <p class="text-sm border-b py-2">
                    {!! $work->description !!}
                    <span class="text-xs block" style="color:var(--crest-muted)">
                        {{ optional($work->subject)->name }}
                        · due {{ optional($work->submission_date)->format('d M') ?? optional($work->date)->format('d M') }}
                        · {{ $doneIds->contains($work->id) ? 'Done' : 'Not marked done' }}
                    </span>
                </p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">No homework this term.</p>
            @endforelse
        </div>
        <div class="bg-white border p-4">
            <h2 class="font-medium mb-2">Recent attendance</h2>
            @forelse($attendanceRows as $row)
                <p class="text-sm border-b py-2">
                    {{ optional($row->date)->format('d M Y') }}
                    · {{ ucfirst($row->mark ?: ($row->status ? 'present' : 'absent')) }}
                    @if($row->remarks) · {{ $row->remarks }} @endif
                </p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">No attendance yet.</p>
            @endforelse
            <h2 class="font-medium mb-2 mt-6">Circulars</h2>
            @forelse($circulars as $row)
                <p class="text-sm border-b py-2">
                    {{ $row->subject }}
                    <span class="text-xs block" style="color:var(--crest-muted)">{{ optional($row->fired_at)->format('d M Y') ?? optional($row->created_at)->format('d M Y') }}</span>
                </p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">No circulars yet.</p>
            @endforelse
            <h2 class="font-medium mb-2 mt-6">Teacher comments</h2>
            @forelse($remarks as $note)
                <p class="text-sm border-b py-2">{{ $note->notes }} <span class="text-xs" style="color:var(--crest-muted)">{{ $note->created_at->format('d M Y') }}</span></p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">No comments yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
