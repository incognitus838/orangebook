@extends($layout)

@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Announcements</h1>
    </div>
    @include('partials.message')

    @if(!empty($canAdd))
        <form method="POST" action="{{ $action }}" class="ob-form mb-6">
            @csrf
            @if(!empty($fullTarget))
                @include('crest.board._target')
            @elseif(!empty($classes) && $classes->count())
                <label>{{ optional(Auth::user()->school)->classWordOne() ?? 'Class' }}</label>
                <select name="class_id">
                    <option value="">Everyone</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
                    @endforeach
                </select>
            @endif
            <label>Title</label>
            <input type="text" name="title" required maxlength="120">
            <label>Notice</label>
            <textarea name="body" rows="3" required maxlength="240"></textarea>
            <button class="crest-btn" type="submit">Post notice</button>
        </form>
    @endif

    @if(!empty($canAdd) && !empty($fullTarget))
        <form method="POST" action="{{ url('/admin/groups') }}" class="ob-form mb-6">
            @csrf
            <p class="ob-form-title">Add a group</p>
            <p class="ob-help">{{ optional(Auth::user()->school)->groupHint() }}</p>
            <label>Group name</label>
            <input type="text" name="name" required maxlength="80">
            <label>Members</label>
            @include('crest.board._people_pick', ['people' => $people, 'name' => 'user_ids', 'withEmail' => false])
            <button class="crest-btn" type="submit">Save group</button>
        </form>
    @endif

    @forelse($notices as $notice)
        <div class="bg-white border p-4 mb-3" style="max-width:640px">
            <p class="text-sm font-medium">{{ $notice->title }}</p>
            <p class="text-xs mt-1" style="color:var(--crest-muted)">
                {{ optional($notice->publish_date)->format('d M Y') ?? optional($notice->created_at)->format('d M Y') }}
                · {{ $notice->standardLink_id ? (optional($notice->standardLink)->StandardSection ?? 'Class') : 'Posted' }}
            </p>
            @if($notice->description)
                <p class="text-sm mt-2">{{ $notice->description }}</p>
            @endif
        </div>
    @empty
        <p class="bg-white border p-4" style="max-width:640px">No notices yet.</p>
    @endforelse
</div>
@endsection
