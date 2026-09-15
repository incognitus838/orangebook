<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Crest')</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/crest.css') }}" rel="stylesheet">
</head>
<body class="crest-shell">
    <header class="crest-nav">
        <a class="crest-wordmark" href="{{ url('/') }}">Crest</a>
        <nav class="crest-nav-links">
            <a href="{{ url('/#plans') }}">Plans</a>
            <a href="{{ url('/join') }}">Parent join</a>
            @guest
                <a href="{{ url('/login') }}">Log in</a>
                <a class="crest-btn" href="{{ url('/start') }}">Start trial</a>
            @else
                <a href="{{ url('/login') }}">Home</a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('crest-logout').submit();">Log out</a>
                <form id="crest-logout" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
            @endguest
        </nav>
    </header>
    @yield('content')
    <footer class="crest-footer">
        <span>Crest for Nigerian secondary schools</span>
        <span>JSS1–SS3 · three terms · WAEC A1–F9</span>
    </footer>
</body>
</html>
