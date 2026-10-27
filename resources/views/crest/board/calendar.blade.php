@extends($layout)

@php
    $view = in_array(request('view'), ['list', 'month'], true) ? request('view') : 'month';
    try {
        $month = request('month') ? \Carbon\Carbon::parse(request('month').'-01')->startOfMonth() : now()->startOfMonth();
    } catch (\Throwable $e) {
        $month = now()->startOfMonth();
    }
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $start = $month->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $end = $month->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $byDay = collect();
    foreach ($events as $event) {
        $from = optional($event->start_date) ? $event->start_date->copy()->startOfDay() : null;
        $to = optional($event->end_date) ? $event->end_date->copy()->startOfDay() : $from;
        if (!$from) {
            continue;
        }
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $byDay[$cursor->toDateString()] = ($byDay[$cursor->toDateString()] ?? collect())->push($event);
            $cursor->addDay();
        }
    }
    $days = [];
    $cursor = $start->copy();
    while ($cursor->lte($end)) {
        $key = $cursor->toDateString();
        $days[] = [
            'date' => $cursor->copy(),
            'inMonth' => $cursor->month === $month->month,
            'isToday' => $cursor->isToday(),
            'events' => $byDay[$key] ?? collect(),
        ];
        $cursor->addDay();
    }
    $query = fn ($extra) => http_build_query(array_filter(array_merge(['view' => $view, 'month' => $month->format('Y-m')], $extra)));
@endphp

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Calendar</h1>
        <div class="cal-switch">
            <a href="?{{ $query(['view' => 'month']) }}" class="{{ $view === 'month' ? 'is-on' : '' }}">Month</a>
            <a href="?{{ $query(['view' => 'list']) }}" class="{{ $view === 'list' ? 'is-on' : '' }}">List</a>
        </div>
    </div>
    @include('partials.message')

    @if(!empty($canAdd))
        <form method="POST" action="{{ $action }}" class="ob-form mb-6">
            @csrf
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <label>Date</label>
            <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" required>
            <label>Title</label>
            <input type="text" name="title" required maxlength="120">
            <label>Notes</label>
            <textarea name="notes" rows="2" maxlength="500"></textarea>
            <button class="crest-btn" type="submit" style="margin-top:24px">Add date</button>
        </form>
    @endif

    @if($view === 'month')
        <div class="cal-nav">
            <a href="?{{ $query(['month' => $prev]) }}">Previous</a>
            <h2>{{ $month->format('F Y') }}</h2>
            <a href="?{{ $query(['month' => $next]) }}">Next</a>
        </div>
        <div class="cal-grid">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
                <div class="cal-dow">{{ $dow }}</div>
            @endforeach
            @foreach($days as $day)
                <div class="cal-cell {{ $day['inMonth'] ? '' : 'is-out' }} {{ $day['isToday'] ? 'is-today' : '' }}">
                    <a class="cal-num" href="?{{ $query(['date' => $day['date']->toDateString()]) }}">{{ $day['date']->format('j') }}</a>
                    @foreach($day['events']->take(3) as $event)
                        <p class="cal-item" title="{{ $event->title }}">{{ $event->title }}</p>
                    @endforeach
                    @if($day['events']->count() > 3)
                        <p class="cal-more">+{{ $day['events']->count() - 3 }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        @forelse($events as $event)
            <div class="bg-white border p-4 mb-3">
                <p class="text-sm font-medium">{{ $event->title }}</p>
                <p class="text-xs mt-1" style="color:var(--crest-muted)">
                    {{ optional($event->start_date)->format('D d M Y') }}
                    @if($event->end_date && optional($event->end_date)->toDateString() !== optional($event->start_date)->toDateString())
                        – {{ $event->end_date->format('d M Y') }}
                    @endif
                </p>
                @if($event->description)
                    <p class="text-sm mt-2">{{ $event->description }}</p>
                @endif
            </div>
        @empty
            <p class="bg-white border p-4">No dates on the calendar yet.</p>
        @endforelse
    @endif
</div>
@endsection
