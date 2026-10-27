@extends('layouts.marketing')

@section('title', 'Choose a new password. Orangebook')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>Choose a new password.</h1>
        <p>At least 8 characters. You will use it with your email the next time you log in.</p>
    </aside>
    <div class="crest-split-form">
        <h2>New password</h2>
        <form method="POST" action="{{ url('/password/reset/') }}" class="crest-form" aria-label="Reset password">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus autocomplete="email">
            @if ($errors->has('email'))
                <p class="crest-err" role="alert">{{ $errors->first('email') }}</p>
            @endif
            <label for="password">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @if ($errors->has('password'))
                <p class="crest-err" role="alert">{{ $errors->first('password') }}</p>
            @endif
            <label for="password-confirm">Confirm new password</label>
            <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
            <button type="submit" class="crest-btn">Save password</button>
        </form>
        <p class="ob-auth-foot"><a href="{{ route('login') }}">Back to log in</a></p>
    </div>
</div>
@endsection
