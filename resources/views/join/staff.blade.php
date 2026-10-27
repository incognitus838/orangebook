@extends('layouts.marketing')

@section('title', 'Join as staff')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>{{ $invite->school->name }}</h1>
        <p>{{ $invite->name }}, set a password to join as a teacher. This invite expires {{ $invite->expires_at->format('d M Y') }}.</p>
    </aside>
    <div class="crest-split-form">
        <h2>Set your password</h2>
        @include('partials.message')
        <form method="POST" action="{{ url('/staff/join/'.$invite->token) }}" class="crest-form">
            @csrf
            <label>Email</label>
            <input value="{{ $invite->email }}" disabled>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required minlength="6">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
            <button class="crest-btn" type="submit" style="margin-top:28px">Join school</button>
        </form>
    </div>
</div>
@endsection
