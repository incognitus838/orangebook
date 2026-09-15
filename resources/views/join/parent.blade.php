@extends('layouts.marketing')

@section('title', 'Join as a parent')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>Prove you know your child.</h1>
        <p style="color:rgba(255,255,255,.72);max-width:32ch;font-size:16px;line-height:1.5;margin-top:16px;">Use the school join code, the admission number on the report card, and the student’s date of birth. You will only see that child.</p>
    </aside>
    <div class="crest-split-form">
        <h2 style="font-size:28px;margin:0 0 8px;">Join as a parent</h2>
        @include('partials.message')
        <form method="POST" action="{{ url('/join') }}" class="crest-form">
            @csrf
            <label for="join_code">School join code</label>
            <input id="join_code" name="join_code" value="{{ old('join_code', $code) }}" required placeholder="CREST-K7P2" style="text-transform:uppercase">
            <label for="admission_no">Admission number</label>
            <input id="admission_no" name="admission_no" value="{{ old('admission_no') }}" required>
            <label for="date_of_birth">Student’s date of birth</label>
            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
            <label for="name">Your name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            <label for="email">Your email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required minlength="6">
            <button class="crest-btn" type="submit" style="margin-top:28px">Link my child</button>
        </form>
    </div>
</div>
@endsection
