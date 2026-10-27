@extends('layouts.parent.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Announcements</h1>
    </div>
    @include('partials.message')
    @foreach($notices ?? [] as $notice)
        <div class="bg-white border p-4 mb-3">
            <p class="text-sm font-medium">{{ $notice->title }}</p>
            <p class="text-xs mt-1" style="color:var(--crest-muted)">{{ optional($notice->publish_date)->format('d M Y') }}</p>
            <p class="text-sm mt-2" style="white-space:pre-wrap">{{ $notice->full_body ?: $notice->description }}</p>
        </div>
    @endforeach
    @forelse($rows as $row)
        <div class="bg-white border p-4 mb-3">
            <p class="text-sm font-medium">{{ $row->subject }}</p>
            <p class="text-xs mt-1" style="color:var(--crest-muted)">{{ optional($row->fired_at)->format('d M Y') ?? optional($row->created_at)->format('d M Y') }}</p>
            @if($row->message)
                <p class="text-sm mt-2" style="white-space:pre-wrap">{{ \Illuminate\Support\Str::limit($row->message, 600) }}</p>
            @endif
        </div>
    @empty
        <p class="bg-white border p-4">No announcements yet.</p>
    @endforelse
</div>
@endsection
