@extends('layouts.teacher.layout')

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $class->StandardSection }}</h1>
        <a href="{{ url('/teacher/class/'.$class->id.'/results') }}" class="crest-btn">Enter results</a>
    </div>
    @include('partials.message')
    <p class="ob-help">
        {{ $isFormTeacher ? 'You are the form teacher.' : 'You teach this class.' }}
        @if($term) · {{ $term->name }} @endif
        @if($settings) · CA {{ $settings->ca_max }} / Mid-term {{ $settings->midterm_max }} / Exam {{ $settings->exam_max }} @endif
        @if($settings && optional(Auth::user()->school)->usesParents()) · WAEC A1–F9 @endif
    </p>
    <div class="day-stats">
        <div class="day-stat">
            <span class="day-stat-n">{{ $students->count() }}</span>
            <span class="day-stat-l">{{ optional(Auth::user()->school)->learnerWord() }}</span>
        </div>
        <div class="day-stat">
            <span class="day-stat-n">{{ $percent }}%</span>
            <span class="day-stat-l">Present today</span>
        </div>
    </div>

    @if($students->isEmpty())
        <p class="bg-white border p-4">No students in this class yet. Ask the principal to add them under Users.</p>
    @else
        <form method="GET" class="mb-3 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs mb-1" style="color:var(--crest-muted)">Date</label>
                <input type="date" name="date" value="{{ $today }}" class="border px-2 py-1" onchange="this.form.submit()">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--crest-muted)">Session</label>
                <select name="session" class="border px-2 py-1" onchange="this.form.submit()">
                    <option value="forenoon" @selected($session === 'forenoon')>Morning</option>
                    <option value="afternoon" @selected($session === 'afternoon')>Afternoon</option>
                </select>
            </div>
        </form>

        <div class="bg-white border p-4 mb-6 overflow-x-auto">
            <h2 class="font-semibold mb-3">This week</h2>
            <table class="crest-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        @foreach($weekDays as $d)
                            <th>{{ \Carbon\Carbon::parse($d)->format('D d') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $row)
                        <tr>
                            <td>{{ $row->user->name ?? 'Student' }}</td>
                            @foreach($weekDays as $d)
                                @php
                                    $cell = optional(($weekMarks->get($row->user_id) ?? collect())->first(fn ($a) => $a->date && $a->date->format('Y-m-d') === $d));
                                    $letter = ['present' => 'P', 'late' => 'L', 'absent' => 'A', 'excused' => 'E'][$cell->mark ?? ''] ?? '·';
                                @endphp
                                <td title="{{ $cell->remarks ?? '' }}">{{ $letter }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-xs mt-2" style="color:var(--crest-muted)">P present · L late · A absent · E excused</p>
        </div>

        <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/attendance') }}" class="bg-white border p-4 mb-8">
            @csrf
            <input type="hidden" name="date" value="{{ $today }}">
            <input type="hidden" name="session" value="{{ $session }}">
            <p class="text-sm mb-3">Mark {{ \Carbon\Carbon::parse($today)->format('l d M') }} · {{ $session === 'afternoon' ? 'Afternoon' : 'Morning' }}. Late, absent and excused need a reason.</p>
            <table class="crest-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Mark</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $row)
                        @php
                            $uid = $row->user_id;
                            $current = optional($marks->get($uid))->mark ?? 'present';
                        @endphp
                        <tr>
                            <td>{{ $row->user->name ?? 'Student' }}</td>
                            <td>
                                <select name="marks[{{ $uid }}]" class="border px-2 py-1 js-mark">
                                    @foreach(['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'] as $value => $label)
                                        <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="remarks[{{ $uid }}]" value="{{ optional($marks->get($uid))->remarks }}" class="border px-2 py-1 w-full js-reason" placeholder="Required if late, absent or excused">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="crest-btn" style="margin-top:16px">Save attendance</button>
        </form>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-white border p-4">
                <h2 class="font-semibold mb-3">Homework</h2>
                <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/homework') }}" class="mb-4">
                    @csrf
                    @if($subjects->count())
                        <label class="block text-xs mb-1" style="color:var(--crest-muted)">Subject</label>
                        <select name="subject_id" class="border px-2 py-1 mb-2 w-full">
                            <option value="">General</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label class="block text-xs mb-1" style="color:var(--crest-muted)">Due</label>
                    <input type="date" name="submission_date" value="{{ now()->addDay()->toDateString() }}" class="border px-2 py-1 mb-2 w-full" required>
                    <textarea name="description" class="border px-2 py-1 w-full mb-2" rows="3" placeholder="What should they do?" required></textarea>
                    <button class="crest-btn" type="submit">Set homework</button>
                </form>
                @forelse($homeworks as $work)
                    <p class="text-sm border-b py-2">
                        {!! $work->description !!}
                        <span class="text-xs block" style="color:var(--crest-muted)">
                            {{ optional($work->subject)->name }}
                            · due {{ optional($work->submission_date)->format('d M') ?? optional($work->date)->format('d M') }}
                        </span>
                    </p>
                @empty
                    <p class="text-sm" style="color:var(--crest-muted)">No homework yet.</p>
                @endforelse
            </div>
            <div class="bg-white border p-4">
                <h2 class="font-semibold mb-3">Student remarks</h2>
                @foreach($students as $row)
                    <div class="mb-4 border-b pb-3">
                        <p class="text-sm font-medium">{{ $row->user->name ?? 'Student' }}</p>
                        @foreach($remarks->get($row->user_id, collect())->take(2) as $note)
                            <p class="text-xs" style="color:var(--crest-muted)">{{ $note->notes }}</p>
                        @endforeach
                        <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/remarks/'.$row->user_id) }}" class="mt-2 flex gap-2">
                            @csrf
                            <input type="text" name="notes" class="border px-2 py-1 flex-1 text-sm" placeholder="Add a remark" required>
                            <button class="text-sm bg-gray-200 px-3 py-1">Save</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
<script>
(function () {
  document.querySelectorAll('tr').forEach(function (row) {
    var mark = row.querySelector('.js-mark');
    var reason = row.querySelector('.js-reason');
    if (!mark || !reason) return;
    function sync() {
      var needs = ['late', 'absent', 'excused'].indexOf(mark.value) !== -1;
      reason.required = needs;
    }
    mark.addEventListener('change', sync);
    sync();
  });
})();
</script>
@endsection
