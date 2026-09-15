@extends('layouts.parent.layout')

@section('content')
<div class="">
    <h1 class="admin-h1 my-3">My children</h1>
    <p class="text-sm text-gray-600 mb-4">You can only see students linked to this parent account.</p>

    @forelse($cards as $card)
        <div class="bg-white border p-4 mb-4">
            <p class="text-lg font-semibold">{{ $card['child']->name }}</p>
            <p class="text-sm text-gray-600 mb-2">{{ $card['className'] ?: 'Class not assigned' }}</p>
            <p class="text-sm mb-2">
                @if($card['termResult'] && $card['termResult']->published_at)
                    Average <strong>{{ $card['termResult']->average }}</strong>
                    · Position <strong>{{ $card['termResult']->position }} / {{ $card['termResult']->class_size }}</strong>
                    · Grade <strong>{{ $card['termResult']->grade }}</strong>
                @else
                    Results not published yet.
                @endif
                @if($card['attendancePercent'] !== null)
                    · Attendance <strong>{{ $card['attendancePercent'] }}%</strong>
                @endif
            </p>
            @if($card['remark'])
                <p class="text-sm text-gray-700 mb-2">Teacher: “{{ $card['remark']->notes }}”</p>
            @endif
            <a href="{{ url('/parent/child/'.$card['child']->id) }}" class="text-sm text-indigo-700 font-medium">Open record</a>
        </div>
    @empty
        <p class="text-gray-700">No child is linked to this account. Ask the school admin to attach your ward.</p>
    @endforelse
</div>
@endsection
