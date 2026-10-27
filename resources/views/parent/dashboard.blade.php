@extends('layouts.parent.layout')

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">My children</h1>
    </div>
    <p class="text-sm mb-4" style="color:var(--crest-muted)">Only learners linked to this login.</p>
    @include('partials.message')

    @forelse($cards as $card)
        <div class="ob-record mb-4" style="max-width:none">
            <p class="ob-child-name">{{ $card['child']->name }}</p>
            <p class="text-sm" style="color:var(--crest-muted);margin:4px 0 16px">{{ $card['className'] ?: 'No class yet' }}</p>
            <div class="day-stats" style="margin-bottom:16px">
                <div class="day-stat">
                    <span class="day-stat-n">
                        @if($card['termResult'] && $card['termResult']->published_at)
                            {{ $card['termResult']->average }}
                        @else
                            —
                        @endif
                    </span>
                    <span class="day-stat-l">Average</span>
                </div>
                <div class="day-stat">
                    <span class="day-stat-n">
                        @if($card['termResult'] && $card['termResult']->published_at && $card['termResult']->position)
                            {{ $card['termResult']->position }}/{{ $card['termResult']->class_size }}
                        @else
                            —
                        @endif
                    </span>
                    <span class="day-stat-l">Position</span>
                </div>
                <div class="day-stat">
                    <span class="day-stat-n">{{ $card['attendancePercent'] !== null ? $card['attendancePercent'].'%' : '—' }}</span>
                    <span class="day-stat-l">Attendance</span>
                </div>
                <div class="day-stat">
                    <span class="day-stat-n">
                        @if($card['termResult'] && $card['termResult']->published_at)
                            {{ $card['termResult']->grade }}
                        @else
                            —
                        @endif
                    </span>
                    <span class="day-stat-l">Grade</span>
                </div>
            </div>
            @if($card['remark'])
                <blockquote class="ob-quote">“{{ $card['remark']->notes }}”<cite>Form teacher</cite></blockquote>
            @endif
            <a class="crest-btn crest-btn-ghost" href="{{ url('/parent/child/'.$card['child']->id) }}">Open full record</a>
        </div>
    @empty
        <p class="bg-white border p-4" style="border-radius:var(--ob-radius-lg)">No child is linked yet. Join with the school code at {{ url('/join') }}, or ask the school to link you.</p>
    @endforelse
</div>
@endsection
