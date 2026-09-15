@extends('layouts.marketing')

@section('title', optional($tenantSchool)->name ? optional($tenantSchool)->name.' — Log in' : 'Log in to Crest')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>{{ optional($tenantSchool)->name ?? 'Log in to your school.' }}</h1>
        <p style="color:rgba(255,255,255,.72);max-width:30ch;font-size:16px;line-height:1.5;margin-top:16px;">
            Principals, teachers, students and parents each have their own home. Data never crosses schools.
        </p>
    </aside>
    <div class="crest-split-form">
        <h2 style="font-size:28px;margin:0 0 8px;">Log in</h2>
        @if(!empty($tenantSchool))
            <p style="color:var(--crest-muted);">{{ $tenantSchool->name }}</p>
        @endif
        @include('partials.message')
        @if(\Config::get('settings.login_status')==0)
            <p>Login is under maintenance.</p>
        @else
        <form method="POST" action="{{ route('login') }}" class="crest-form" aria-label="Login">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @if ($errors->has('email'))
                <p class="crest-err">{{ $errors->first('email') }}</p>
            @endif
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @if ($errors->has('password'))
                <p class="crest-err">{{ $errors->first('password') }}</p>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;font-size:13px;">
                <label style="margin:0;"><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember me</label>
                <a href="{{ route('password.request') }}">Forgot password</a>
            </div>
            <button class="crest-btn" type="submit" id="login" style="margin-top:28px;width:100%;">Log in</button>
        </form>
        @endif
    </div>
</div>
@endsection
