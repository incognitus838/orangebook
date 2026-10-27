@extends('layouts.marketing')

@section('title', 'Join as a parent')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>Join with the school code.</h1>
        <p>The principal gives you a join code. You type your child’s name. You will only see that child.</p>
    </aside>
    <div class="crest-split-form">
        <h2>Join as a parent</h2>
        @include('partials.message')
        <form method="POST" action="{{ url('/join') }}" class="crest-form">
            @csrf
            <label for="join_code">School join code</label>
            <input id="join_code" name="join_code" value="{{ old('join_code', $code) }}" required placeholder="SCHOOL CODE" autocomplete="off" style="text-transform:uppercase">
            <label for="child_name">Child’s name</label>
            <input id="child_name" name="child_name" value="{{ old('child_name') }}" required>
            <label for="admission_no">Admission number (if two children share a name)</label>
            <input id="admission_no" name="admission_no" value="{{ old('admission_no') }}">
            <label for="name">Your name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            <label for="email">Your email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone') }}">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required minlength="6">
            <button class="crest-btn" type="submit">Link my child</button>
        </form>
        <p style="margin-top:20px;font-size:14px;color:var(--crest-muted)">Student? <a href="{{ url('/join/student') }}">Open your desk</a>.</p>
    </div>
</div>
@endsection
