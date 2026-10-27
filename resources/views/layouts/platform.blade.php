<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Orangebook platform')</title>
    @include('layouts.partials.favicon')
    <link href="{{ asset('css/crest.css') }}" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600&family=Instrument+Serif&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-paper.css') }}?v={{ @filemtime(public_path('css/ob-paper.css')) }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/ob-saas.css') }}?v={{ @filemtime(public_path('css/ob-saas.css')) }}" rel="stylesheet">
</head>
<body class="ob-app font-primary antialiased min-h-screen overflow-x-hidden">
@php
    $obLinks = [
        ['Overview', url('/platform'), request()->is('platform')],
        ['Organisations', url('/platform/organisations'), request()->is('platform/organisations', 'platform/organisations/*')],
        ['Applications', url('/platform/applications'), request()->is('platform/applications', 'platform/applications/*')],
    ];
@endphp
<nav class="navbar ob-bar">
    <div class="nav-brand ob-bar-brand">
        <button type="button" class="crest-menu-btn ob-menu-btn" onclick="showsidebar('res_sidebar')" aria-label="Open menu">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <a class="crest-school-name ob-bar-school" href="{{ url('/platform') }}"><strong>Orangebook</strong></a>
    </div>
    <div class="ob-bar-end">
        <details class="ob-account" dusk="profile-menu">
            <summary class="ob-account-btn" aria-label="Account">
                @include('layouts.partials.account-face', ['size' => 'sm'])
            </summary>
            <div class="ob-account-menu">
                <div class="ob-account-id">
                    @include('layouts.partials.account-face', ['size' => 'md'])
                    <div>
                        <a class="ob-account-name" href="{{ url('/platform') }}">{{ Auth::user()->name }}</a>
                        <p class="ob-account-email">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <a class="ob-account-link" dusk="logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('plo').submit();">Log out</a>
            </div>
        </details>
        <form id="plo" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
    </div>
</nav>
<div class="ob-frame">
    <div class="ob-rail ob-rail-desktop">
        <ul class="ob-rail-list">
            @foreach($obLinks as [$obLabel, $obHref, $obOn])
                <li class="ob-rail-item {{ $obOn ? 'active' : '' }}">
                    <a href="{{ $obHref }}" class="ob-rail-link" @if($obOn) aria-current="page" @endif><span>{{ $obLabel }}</span></a>
                </li>
            @endforeach
        </ul>
    </div>
    <div id="res_sidebar" class="hidden res_sidebar" onclick="if(event.target===this)showsidebar('res_sidebar')">
        <div class="min-h-full header-wrapper-b ob-rail ob-rail-drawer" onclick="event.stopPropagation()">
            <button type="button" class="crest-rail-close ob-rail-close" onclick="showsidebar('res_sidebar')">Close</button>
            <ul class="ob-rail-list">
                @foreach($obLinks as [$obLabel, $obHref, $obOn])
                    <li class="ob-rail-item {{ $obOn ? 'active' : '' }}">
                        <a href="{{ $obHref }}" class="ob-rail-link" @if($obOn) aria-current="page" @endif><span>{{ $obLabel }}</span></a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <main class="ob-main">
        @include('partials.message')
        @yield('content')
    </main>
</div>
<script>
function showsidebar(id) {
    var el = document.getElementById(id);
    if (!el) return;
    var open = el.classList.contains('is-open') || (el.classList.contains('block') && !el.classList.contains('hidden'));
    if (open) {
        el.classList.remove('is-open', 'block');
        el.classList.add('hidden');
        document.body.classList.remove('crest-rail-open');
    } else {
        el.classList.add('is-open', 'block');
        el.classList.remove('hidden');
        document.body.classList.add('crest-rail-open');
    }
}
document.addEventListener('click', function (e) {
    document.querySelectorAll('details.ob-account[open]').forEach(function (el) {
        if (!el.contains(e.target)) el.removeAttribute('open');
    });
});
</script>
</body>
</html>
