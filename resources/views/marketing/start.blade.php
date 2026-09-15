@extends('layouts.marketing')

@section('title', 'Open a school on Crest')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>Your school. Your records. Live today.</h1>
        <p style="color:rgba(255,255,255,.72);max-width:32ch;font-size:16px;line-height:1.5;margin-top:16px;">We create the tenant, the admin login and a 2025/2026 session with JSS and SS already laid out.</p>
    </aside>
    <div class="crest-split-form">
        <h2 style="font-size:28px;margin:0 0 8px;">Open a Crest school</h2>
        <p style="color:var(--crest-muted);margin:0 0 8px;">You can change plan after you log in.</p>
        <form method="POST" action="{{ url('/start') }}" class="crest-form">
            @csrf
            <label for="school_name">School name</label>
            <input id="school_name" name="school_name" value="{{ old('school_name') }}" required>
            @error('school_name')<p class="crest-err">{{ $message }}</p>@enderror
            <label for="admin_name">Your name</label>
            <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<p class="crest-err">{{ $message }}</p>@enderror
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone') }}">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            <label for="plan">Plan</label>
            <select id="plan" name="plan">
                @foreach($plans as $plan)
                    <option value="{{ $plan->name }}" @selected($selected === $plan->name)>{{ $plan->display_name }}</option>
                @endforeach
            </select>
            <button class="crest-btn" type="submit" style="margin-top:28px;">Create school</button>
        </form>
    </div>
</div>
@endsection
