@extends('layouts.marketing')

@section('title', 'Reset your password. Orangebook')

@section('content')
<div class="crest-split">
    <aside class="crest-split-ink">
        <h1>Locked out? It happens.</h1>
        <p>Type the email you log in with. We send a link that lets you choose a new password.</p>
    </aside>
    <div class="crest-split-form">
        <h2>Reset password</h2>
        @if (session('status'))
            <p class="ob-flash" role="status">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="crest-form" aria-label="Reset password">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@school.ng">
            @if ($errors->has('email'))
                <p class="crest-err" role="alert">{{ $errors->first('email') }}</p>
            @endif
            <button type="submit" class="crest-btn">Send reset link</button>
        </form>
        <p class="ob-auth-foot">Remembered it? <a href="{{ route('login') }}">Back to log in</a></p>
    </div>
</div>
@endsection
