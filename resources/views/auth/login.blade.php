@extends('layouts.marketing')

@section('title', optional($tenantSchool)->name ? 'Log in. '.optional($tenantSchool)->name : 'Log in to Orangebook')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>{{ optional($tenantSchool)->name ?? 'One login for the whole school.' }}</h1>
        <p>
            Use your email or admission number. We send you to the principal, teacher, student or parent desk from your role.
        </p>
    </aside>
    <div class="crest-split-form">
        <h2>Welcome back</h2>
        @if(!empty($tenantSchool))
            <p style="color:var(--crest-muted);">{{ $tenantSchool->name }}</p>
        @endif
        @include('partials.message')
        @if(\Config::get('settings.login_status')==0)
            <p>Login is under maintenance.</p>
        @else
        <form method="POST" action="{{ route('login') }}" class="crest-form" aria-label="Login">
            @csrf
            <label for="email">Email or admission number</label>
            <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@school.ng or IKJ/26/0001">
            @if ($errors->has('email'))
                <p class="crest-err">{{ $errors->first('email') }}</p>
            @endif
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @if ($errors->has('password'))
                <p class="crest-err">{{ $errors->first('password') }}</p>
            @endif
            <div class="ob-auth-meta">
                <label style="margin:0;"><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember me</label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <button class="crest-btn" type="submit" id="login" style="width:100%;">Log in</button>
        </form>
        <p class="ob-auth-foot">
            Parent without a login? <a href="{{ url('/join') }}">Join with a school code</a><br>
            Student? <a href="{{ url('/join/student') }}">Open your desk</a>
        </p>
        @endif
    </div>
</div>
@endsection
