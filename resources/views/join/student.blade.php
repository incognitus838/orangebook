@extends('layouts.marketing')

@section('title', 'Join as a student')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>Open your desk.</h1>
        <p>The school gives you a join code. You type your name and admission number from the report card, then choose a password.</p>
    </aside>
    <div class="crest-split-form">
        <h2>Join as a student</h2>
        @include('partials.message')
        <form method="POST" action="{{ url('/join/student') }}" class="crest-form">
            @csrf
            <label for="join_code">School join code</label>
            <input id="join_code" name="join_code" value="{{ old('join_code', $code) }}" required placeholder="SCHOOL CODE" autocomplete="off" style="text-transform:uppercase">
            <label for="child_name">Your name</label>
            <input id="child_name" name="child_name" value="{{ old('child_name') }}" required>
            <label for="admission_no">Admission number</label>
            <input id="admission_no" name="admission_no" value="{{ old('admission_no') }}" required>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required minlength="6">
            <button class="crest-btn" type="submit">Open my desk</button>
        </form>
        <p style="margin-top:20px;font-size:14px;color:var(--crest-muted)">Parent? <a href="{{ url('/join') }}">Join with a school code</a>.</p>
    </div>
</div>
@endsection
