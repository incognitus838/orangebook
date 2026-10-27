@extends($layout)

@php
    $role = $day['role'] ?? 'principal';
    $stats = $day['stats'] ?? [];
    $charts = $day['charts'] ?? ['week' => collect(), 'today' => [], 'strength' => collect()];
    $week = collect($charts['week'] ?? []);
    $today = $charts['today'] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'marked' => 0];
    $strength = collect($charts['strength'] ?? []);
    $maxStrength = max(1, (int) $strength->max('count'));
    $weekTotal = (int) ($charts['week_total'] ?? $week->sum(fn ($d) => ($d['present'] ?? 0) + ($d['absent'] ?? 0)));
    $unpublishedList = collect($day['unpublished'] ?? [])->take(8);
    $unpublishedMore = collect($day['unpublished'] ?? [])->count() - $unpublishedList->count();
    $isStudent = $role === 'student';
    $firstName = \Illuminate\Support\Str::title(mb_strtolower((string) (strtok((string) Auth::user()->name, ' ') ?: optional(Auth::user()->userprofile)->firstname)));
@endphp

@section('content')
<div class="day-board">
    <h1 class="admin-h1 my-3">{{ $isStudent ? 'Hello, '.$firstName : 'Dashboard' }}</h1>
    <p class="day-sub">{{ now()->format('l, j F') }}@if($isStudent && $day['classToday']) · {{ $day['classToday']->StandardSection }}@endif</p>
    @include('partials.message')

    @if($role === 'principal' && !empty($day['intakeOpen']))
        <p class="ob-help"><a href="{{ url('/admin/students/intake') }}">New intake</a> is open for the first weeks of this year.</p>
    @endif

    @if(in_array($role, ['principal', 'teacher'], true))
        <div class="day-stats">
            <a class="day-stat is-students" href="{{ $role === 'principal' ? url('/admin/students') : $day['classUrl'] }}">
                <span class="day-stat-n">{{ number_format($stats['students'] ?? 0) }}</span>
                <span class="day-stat-l">{{ optional(Auth::user()->school)->learnerWord() }}</span>
            </a>
            @if($role === 'principal')
                <a class="day-stat is-teachers" href="{{ url('/admin/teachers') }}">
                    <span class="day-stat-n">{{ number_format($stats['teachers'] ?? 0) }}</span>
                    <span class="day-stat-l">{{ optional(Auth::user()->school)->staffWord() }}</span>
                </a>
            @endif
            <div class="day-stat is-present">
                <span class="day-stat-n">{{ $stats['present_pct'] !== null ? $stats['present_pct'].'%' : '—' }}</span>
                <span class="day-stat-l">Present today</span>
            </div>
            <div class="day-stat is-absent">
                <span class="day-stat-n">{{ number_format($stats['absent_today'] ?? 0) }}</span>
                <span class="day-stat-l">Absent today</span>
            </div>
            <a class="day-stat is-pending" href="{{ $day['resultsUrl'] }}">
                <span class="day-stat-n">{{ number_format($stats['unpublished'] ?? 0) }}</span>
                <span class="day-stat-l">Unpublished</span>
            </a>
        </div>

        <div class="day-charts">
            <div class="day-chart">
                <h2>Attendance this week</h2>
                @if($weekTotal === 0)
                    <p class="day-chart-note">No attendance marked this week yet. The chart fills as the register is taken.</p>
                @endif
                <div class="day-chart-frame">
                    <canvas id="weekChart" height="220"></canvas>
                </div>
            </div>
            <div class="day-chart">
                <h2>{{ $role === 'principal' ? (optional(Auth::user()->school)->learnerWord() ?? 'Students') : 'The hall today' }}</h2>
                <div class="day-chart-frame day-chart-frame-round">
                    <canvas id="graph" height="220"></canvas>
                </div>
                @if($role === 'principal')
                    <div class="day-legend">
                        <span><i style="background:#ea580c"></i> Boys {{ number_format($stats['boys'] ?? 0) }}</span>
                        <span><i style="background:#7c3aed"></i> Girls {{ number_format($stats['girls'] ?? 0) }}</span>
                    </div>
                @else
                    <div class="day-legend">
                        <span><i style="background:#16a34a"></i> Present {{ $today['present'] ?? 0 }}</span>
                        <span><i style="background:#f59e0b"></i> Late {{ $today['late'] ?? 0 }}</span>
                        <span><i style="background:#dc2626"></i> Absent {{ $today['absent'] ?? 0 }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if($role === 'principal' && $strength->count())
            <div class="day-chart day-strength">
                <h2>{{ optional(Auth::user()->school)->classWordOne() }} strength</h2>
                @foreach($strength as $row)
                    <div class="day-bar-row">
                        <span class="day-bar-label">{{ $row['label'] }}</span>
                        <span class="day-bar-track"><span class="day-bar-fill" style="width: {{ round(($row['count'] / $maxStrength) * 100) }}%"></span></span>
                        <span class="day-bar-n">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <div class="day-lists">
        <div class="bg-white border p-4">
            <p class="text-xs" style="color:var(--crest-muted)">{{ $role === 'principal' ? 'The hall' : ($isStudent ? 'My class' : 'Class today') }}</p>
            @if($day['classToday'])
                <p class="text-xl font-semibold">{{ $day['classToday']->StandardSection }}</p>
                <p class="text-sm mb-2" style="color:var(--crest-muted)">{{ $day['classCount'] }} {{ strtolower(optional(Auth::user()->school)->learnerWord()) }}</p>
                @if(!empty($day['todayMark']))
                    <p class="text-sm">You are marked {{ $day['todayMark']->mark ?? 'present' }} today.</p>
                @elseif($isStudent)
                    <p class="text-sm" style="color:var(--crest-muted)">Today's register has not been taken yet.</p>
                @endif
                @unless($isStudent)
                    <a href="{{ $day['classUrl'] }}" class="text-sm" style="text-decoration:underline">Open class</a>
                @endunless
            @elseif($role === 'principal')
                <p class="text-xl font-semibold">{{ number_format($day['classCount']) }} {{ strtolower(optional(Auth::user()->school)->learnerWord()) }}</p>
                <p class="text-sm mb-2" style="color:var(--crest-muted)">Whole {{ strtolower(optional(Auth::user()->school)->orgWord()) }}</p>
                <a href="{{ $day['classUrl'] }}" class="text-sm" style="text-decoration:underline">{{ optional(Auth::user()->school)->classWord() }}</a>
            @else
                <p class="text-sm" style="color:var(--crest-muted)">No class assigned yet.</p>
            @endif
        </div>

        @unless($isStudent)
        <div class="bg-white border p-4">
            <p class="text-xs" style="color:var(--crest-muted)">Who is absent</p>
            @forelse($day['absentees'] as $row)
                <p class="text-sm border-b py-1">
                    {{ $row->name }}
                    @if($row->class) · {{ $row->class }} @endif
                    · {{ $row->mark }}
                    @if($row->remarks) · {{ $row->remarks }} @endif
                </p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">Nobody marked absent, late or excused today.</p>
            @endforelse
        </div>
        @endunless

        <div class="bg-white border p-4">
            <p class="text-xs" style="color:var(--crest-muted)">{{ $isStudent ? 'Report card' : 'Unpublished results' }}</p>
            @if($isStudent)
                @if($unpublishedList->isNotEmpty())
                    <p class="text-sm" style="color:var(--crest-muted)">This term's report card is not out yet.</p>
                @else
                    <p class="text-sm">Your report card for this term is ready.</p>
                @endif
            @else
            @forelse($unpublishedList as $row)
                <p class="text-sm border-b py-1">
                    {{ $row->class }}
                    <span style="color:var(--crest-muted)"> · {{ $row->label }}</span>
                </p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">Nothing waiting to publish.</p>
            @endforelse
            @if($unpublishedMore > 0)
                <p class="text-sm py-1" style="color:var(--crest-muted)">{{ $unpublishedMore }} more {{ \Illuminate\Support\Str::plural('class', $unpublishedMore) }}</p>
            @endif
            @endif
            <a href="{{ $day['resultsUrl'] }}" class="text-sm" style="text-decoration:underline">{{ $isStudent ? 'View results' : 'Results' }}</a>
        </div>

        <div class="bg-white border p-4">
            <p class="text-xs" style="color:var(--crest-muted)">Homework due</p>
            @forelse($day['homeworkDue'] as $work)
                <p class="text-sm border-b py-1">
                    {!! \Illuminate\Support\Str::limit(strip_tags($work->description), 80) !!}
                    <span class="text-xs block" style="color:var(--crest-muted)">
                        {{ optional($work->standardLink)->StandardSection }}
                        · due {{ optional($work->submission_date)->format('d M') }}
                    </span>
                </p>
            @empty
                <p class="text-sm" style="color:var(--crest-muted)">Nothing due.</p>
            @endforelse
            <a href="{{ $day['homeworkUrl'] }}" class="text-sm" style="text-decoration:underline">{{ $isStudent ? 'All homework' : 'Homework' }}</a>
        </div>
    </div>
</div>
@endsection

@if(in_array($role, ['principal', 'teacher'], true))
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    var week = @json($week->values());
    var today = @json($today);
    var boys = {{ (int) ($stats['boys'] ?? 0) }};
    var girls = {{ (int) ($stats['girls'] ?? 0) }};
    var isPrincipal = @json($role === 'principal');
    var ink = '#ea580c';
    var mark = '#cbd5e1';
    var rule = '#f59e0b';
    var green = '#16a34a', red = '#dc2626', violet = '#7c3aed', grid = '#eef1f5';
    var font = { family: 'Inter, system-ui, sans-serif', size: 12 };
    if (window.Chart) { Chart.defaults.font = font; Chart.defaults.color = '#64748b'; }

    var weekEl = document.getElementById('weekChart');
    if (weekEl && window.Chart) {
        new Chart(weekEl, {
            type: 'bar',
            data: {
                labels: week.map(function (d) { return d.label; }),
                datasets: [
                    { label: 'Present', data: week.map(function (d) { return d.present; }), backgroundColor: ink, borderWidth: 0, borderRadius: 6, borderSkipped: false, maxBarThickness: 36 },
                    { label: 'Absent', data: week.map(function (d) { return d.absent; }), backgroundColor: mark, borderWidth: 0, borderRadius: 6, borderSkipped: false, maxBarThickness: 36 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, useBorderRadius: true, borderRadius: 3, font: font } } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        suggestedMax: Math.max(10, week.reduce(function (n, d) { return Math.max(n, (d.present || 0) + (d.absent || 0)); }, 0)),
                        ticks: { precision: 0 },
                        grid: { color: grid }, border: { display: false }
                    }
                }
            }
        });
    }

    var pieEl = document.getElementById('graph');
    if (pieEl && window.Chart) {
        var pieData = isPrincipal
            ? { labels: ['Boys', 'Girls'], data: [boys, girls], colors: [ink, violet] }
            : { labels: ['Present', 'Late', 'Absent', 'Excused'], data: [today.present || 0, today.late || 0, today.absent || 0, today.excused || 0], colors: [green, rule, red, '#94a3b8'] };
        new Chart(pieEl, {
            type: 'doughnut',
            data: {
                labels: pieData.labels,
                datasets: [{ data: pieData.data, backgroundColor: pieData.colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 4 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }
})();
</script>
@endpush
@endif
