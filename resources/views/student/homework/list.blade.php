@extends('layouts.student.layout')

@section('content')
<div>
    <h1 class="admin-h1 my-3">Homework</h1>
    @include('partials.message')
    @forelse($works as $work)
        <div class="bg-white border p-4 mb-3">
            <p class="text-sm">{!! $work->description !!}</p>
            <p class="text-xs mt-1" style="color:var(--crest-muted)">
                {{ optional($work->subject)->name }}
                · given {{ optional($work->date)->format('d M') }}
                · due {{ optional($work->submission_date)->format('d M Y') ?? '—' }}
            </p>
            @if($doneIds->contains($work->id))
                <p class="text-sm mt-2">Done.</p>
            @else
                <form method="POST" action="{{ url('/student/homeworks/'.$work->id.'/done') }}" class="mt-2">
                    @csrf
                    <button class="crest-btn" type="submit">Mark done</button>
                </form>
            @endif
        </div>
    @empty
        <p class="bg-white border p-4">No homework yet.</p>
    @endforelse
</div>
@endsection
