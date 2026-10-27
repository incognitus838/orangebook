@if(session('platform_user_id'))
    <div class="ob-platform-banner">
        <span>You are inside this organisation from the platform desk.</span>
        <a href="{{ url('/platform/return') }}">Return to platform</a>
    </div>
@elseif(Auth::check() && Auth::user()->isImpersonating())
    <div class="ob-platform-banner">
        <span>You are viewing {{ Auth::user()->name }}’s desk.</span>
        <a href="{{ url('/impersonate/stop') }}">Return to admin</a>
    </div>
@endif
@php
    $obUser = Auth::user();
    $obHome = $obUser ? $obUser->homePath() : url('/');
    $obPrefix = '/admin';
    if ($obUser) {
        if ($obUser->isParent()) {
            $obPrefix = '/parent';
        } elseif ($obUser->isTeacher()) {
            $obPrefix = '/teacher';
        } elseif ($obUser->isStudent()) {
            $obPrefix = '/student';
        }
    }
    $obCanPassword = $obUser && method_exists($obUser, 'isParent') && !$obUser->isParent();
    $obCanProfile = $obUser && ($obUser->isAdmin() || $obUser->isTeacher());
    $obDisplayName = $obUser
        ? (optional($obUser->userprofile)->firstname ? $obUser->FullName : $obUser->name)
        : '';
@endphp
<nav class="navbar ob-bar bg-white w-full flex items-center justify-between">
    <div class="nav-brand ob-bar-brand flex items-center">
        @if($obUser)
            <button type="button" class="crest-menu-btn ob-menu-btn" onclick="showsidebar('res_sidebar')" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            @php
                $obLogo = '-';
                if ($obUser->school) {
                    $obLogo = optional($obUser->SchoolLogo)->meta_value ?? '-';
                }
            @endphp
            @if($obLogo !== '-' && !str_contains($obLogo, 'demologo'))
                <a class="crest-school-logo" href="{{ url($obHome) }}">
                    <img src="{{ asset(ltrim($obLogo, '/')) }}" alt="">
                </a>
            @endif
            <a class="crest-school-name ob-bar-school" href="{{ url($obHome) }}">
                <strong>{{ optional($obUser->school)->name ?? config('app.name') }}</strong>
            </a>
        @else
            @include('layouts.partials.logo')
        @endif
    </div>
    <div class="ob-bar-end flex items-center">
        @auth
            <div class="crest-year ob-bar-year">
                {{ optional(\App\Helpers\SiteHelper::getAcademicYear(Auth::user()->school_id))->name }}
            </div>
        @endauth
        <ul class="navbar-nav ml-auto flex items-center">
            @guest
                <li class="nav-item px-2">
                    <a class="nav-link" href="{{ route('login') }}" id="login">{{ __('Login') }}</a>
                </li>
                <li class="nav-item px-2">
                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @else
                <li>
                    <details class="ob-account" dusk="profile-menu">
                        <summary class="ob-account-btn" aria-label="Account">
                            @include('layouts.partials.account-face', ['size' => 'sm'])
                        </summary>
                        <div class="ob-account-menu">
                            <div class="ob-account-id">
                                @include('layouts.partials.account-face', ['size' => 'md'])
                                <div>
                                    <a class="ob-account-name" href="{{ url($obHome) }}">{{ $obDisplayName }}</a>
                                    <p class="ob-account-email">{{ $obUser->email }}</p>
                                </div>
                            </div>
                            @if($obCanPassword)
                                <a href="{{ url($obPrefix.'/changepassword') }}" dusk="password-link" class="ob-account-link">Change password</a>
                            @endif
                            @if($obCanProfile)
                                <a href="{{ url($obPrefix.'/editprofile') }}" class="ob-account-link">Edit profile</a>
                                <a href="{{ url($obPrefix.'/changeavatar') }}" class="ob-account-link">Change photo</a>
                            @endif
                            @if($obUser->isImpersonating())
                                <a href="{{ url('/impersonate/stop') }}" class="ob-account-link">Return to admin</a>
                            @else
                                <a class="ob-account-link" dusk="logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Log out</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            @endif
                        </div>
                    </details>
                </li>
            @endguest
        </ul>
    </div>
</nav>
@once
<script>
document.addEventListener('click', function (e) {
    document.querySelectorAll('details.ob-account[open]').forEach(function (el) {
        if (!el.contains(e.target)) el.removeAttribute('open');
    });
});
</script>
@endonce
