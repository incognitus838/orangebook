<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Orangebook')</title>
    <meta name="description" content="@yield('meta', 'Apply for Orangebook. Attendance, WAEC results and circulars for Nigerian schools, NGO programmes and training halls. Custom quote. One bill per term.')">
    <meta property="og:title" content="@yield('title', 'Orangebook')">
    <meta property="og:description" content="@yield('meta', 'Apply for Orangebook. Attendance, WAEC results and circulars for Nigerian schools, NGO programmes and training halls.')">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#ea580c">
    @include('layouts.partials.favicon')
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/crest.css') }}" rel="stylesheet">
    <link href="{{ asset('css/landing.css') }}" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600&family=Instrument+Serif&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-paper.css') }}?v={{ @filemtime(public_path('css/ob-paper.css')) }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-saas.css') }}?v={{ @filemtime(public_path('css/ob-saas.css')) }}" rel="stylesheet">
    @stack('head')
</head>
<body class="lp-shell">
    <a class="lp-skip" href="#main">Skip to content</a>
    <header class="lp-bar sx-bar">
        <a class="lp-wordmark sx-logo" href="{{ url('/') }}" @if(url()->current() === url('/')) aria-current="page" @endif>
            <span class="sx-logo-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v15H6.5A2.5 2.5 0 0 0 4 20.5 2.5 2.5 0 0 0 6.5 23H19v-5"/></svg></span>
            Orangebook
        </a>
        <nav class="lp-bar-links sx-bar-links" aria-label="Primary">
            <a href="{{ url('/#features') }}">Features</a>
            <a href="{{ url('/#attendance') }}">Attendance</a>
            <a href="{{ url('/#results') }}">Results</a>
            <a href="{{ url('/#parents') }}">Parents</a>
            <a href="{{ url('/#faq') }}">FAQ</a>
        </nav>
        <div class="sx-bar-cta">
            @guest
                <a class="sx-bar-login" href="{{ url('/login') }}" @if(url()->current() === url('/login')) aria-current="page" @endif>Log in</a>
            @else
                <a class="sx-bar-login" href="{{ url(auth()->user()->homePath()) }}">Dashboard</a>
            @endguest
            <a href="{{ url('/#apply') }}" class="lp-btn lp-btn-sm">Get a quote</a>
        </div>
        <button type="button" class="lp-burger" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </header>
    <div class="lp-overlay">
        <a href="{{ url('/#features') }}">Features</a>
        <a href="{{ url('/#attendance') }}">Attendance</a>
        <a href="{{ url('/#results') }}">Results</a>
        <a href="{{ url('/#parents') }}">Parents</a>
        <a href="{{ url('/#faq') }}">FAQ</a>
        @guest
            <a href="{{ url('/login') }}" @if(url()->current() === url('/login')) aria-current="page" @endif>Log in</a>
        @else
            <a href="{{ url(auth()->user()->homePath()) }}">Dashboard</a>
        @endguest
        <a class="lp-btn lp-btn-sm" href="{{ url('/#apply') }}">Get a quote</a>
    </div>
    <main id="main">
        @yield('content')
    </main>
    <footer class="lp-foot sx-foot">
        <div class="sx-foot-inner">
            <div class="sx-foot-brand">
                <a class="sx-logo" href="{{ url('/') }}">
                    <span class="sx-logo-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v15H6.5A2.5 2.5 0 0 0 4 20.5 2.5 2.5 0 0 0 6.5 23H19v-5"/></svg></span>
                    Orangebook
                </a>
                <p>The mark book for every school. Attendance, results, circulars and parents in one login.</p>
            </div>
            <nav class="sx-foot-col" aria-label="Product">
                <h4>Product</h4>
                <a href="{{ url('/#features') }}">Features</a>
                <a href="{{ url('/#attendance') }}">Attendance</a>
                <a href="{{ url('/#results') }}">Results</a>
                <a href="{{ url('/#parents') }}">Parents</a>
            </nav>
            <nav class="sx-foot-col" aria-label="Get started">
                <h4>Get started</h4>
                <a href="{{ url('/start') }}">Apply for a quote</a>
                <a href="{{ url('/join') }}">Join with a school code</a>
                <a href="{{ url('/join/student') }}">Student desk</a>
                <a href="{{ url('/login') }}">Log in</a>
            </nav>
            <nav class="sx-foot-col" aria-label="Company">
                <h4>Company</h4>
                <a href="mailto:hello@orangebook.ng">hello@orangebook.ng</a>
                <a href="{{ url('/privacy') }}">Privacy</a>
                <a href="{{ url('/terms') }}">Terms</a>
            </nav>
        </div>
        <div class="sx-foot-base">
            <span>&copy; {{ date('Y') }} Orangebook. Every school, every size.</span>
            <span>Made for Nigerian schools</span>
        </div>
    </footer>
    <script src="{{ asset('js/landing.js') }}"></script>
</body>
</html>
