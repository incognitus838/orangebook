@extends($layout)

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Homework</h1>
    </div>
    @include('partials.message')

    @if(!empty($canAdd))
        <form method="POST" action="{{ $action }}" class="ob-form mb-6">
            @csrf
            <label>{{ optional(Auth::user()->school)->classWordOne() ?? 'Class' }}</label>
            <select name="class_id" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
                @endforeach
            </select>
            @if($subjects->count())
                <label>Subject</label>
                <select name="subject_id">
                    <option value="">General</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            @endif
            <label>Due</label>
            <input type="date" name="submission_date" value="{{ now()->addDay()->toDateString() }}" required>
            <label>Work</label>
            <textarea name="description" rows="3" placeholder="What should they do?" required></textarea>
            <button class="crest-btn" type="submit">Set homework</button>
        </form>
    @endif

    @forelse($works as $work)
        <div class="bg-white border p-4 mb-3" style="max-width:640px">
            <p class="text-sm">{!! $work->description !!}</p>
            <p class="text-xs mt-1" style="color:var(--crest-muted)">
                {{ optional($work->standardLink)->StandardSection }}
                @if($work->subject) · {{ $work->subject->name }} @endif
                · given {{ optional($work->date)->format('d M') }}
                · due {{ optional($work->submission_date)->format('d M Y') ?? '—' }}
            </p>
        </div>
    @empty
        <p class="bg-white border p-4" style="max-width:640px">No homework yet.</p>
    @endforelse
</div>
@endsection
