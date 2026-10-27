@extends('layouts.admin.layout')
@section('content')
<div>
    <h1 class="admin-h1 my-3">Invite {{ optional(Auth::user()->school)->staffWord() }}</h1>
    @include('partials.message')
    <p class="text-sm mb-4" style="color:var(--crest-muted)">They get a link and set their own password. They cannot join with the parent join code.</p>
    <form method="POST" action="{{ url('/admin/invite-teacher') }}" class="crest-form bg-white border p-4 max-w-lg" style="border-color:var(--crest-rule)">
        @csrf
        <label>Name</label>
        <input name="name" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <button class="crest-btn" type="submit" style="margin-top:20px">Send invite</button>
    </form>

    <h2 class="admin-h1 mt-8 mb-3">Recent invites</h2>
    <table class="crest-table">
        <tr><th>Name</th><th>Email</th><th>Status</th><th>Link</th></tr>
        @forelse($invites as $invite)
            <tr>
                <td>{{ $invite->name }}</td>
                <td>{{ $invite->email }}</td>
                <td>{{ $invite->accepted_at ? 'Joined' : ($invite->isOpen() ? 'Waiting' : 'Expired') }}</td>
                <td>
                    @if($invite->isOpen())
                        <a href="{{ url('/staff/join/'.$invite->token) }}">Open link</a>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4">No invites yet.</td></tr>
        @endforelse
    </table>
</div>
@endsection
