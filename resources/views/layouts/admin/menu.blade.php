@php
    $school = optional(Auth::user())->school;
    $settingsActive = in_array(Request::segment(2), ['schooldetails', 'academics', 'academic', 'promotions', 'promotion', 'admissions', 'admission', 'marks'])
        || in_array(Request::segment(3), ['generalsettings']);
@endphp
<ul class="ob-rail-list">
    <li class="ob-rail-item {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
        <a href="{{ url('admin/dashboard') }}" class="ob-rail-link" @if(Request::segment(2) == 'dashboard') aria-current="page" @endif><span>Dashboard</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['announcements', 'mail', 'notices', 'notice']) ? 'active' : '' }}">
        <a href="{{ url('/admin/announcements') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['announcements', 'mail', 'notices', 'notice'])) aria-current="page" @endif><span>Announcements</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['teachers', 'teacher']) ? 'active' : '' }}">
        <a href="{{ url('/admin/teachers') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['teachers', 'teacher'])) aria-current="page" @endif><span>{{ $school->staffWord() }}</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['students', 'student']) ? 'active' : '' }}">
        <a href="{{ url('/admin/students') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['students', 'student'])) aria-current="page" @endif><span>{{ $school->learnerWord() }}</span></a>
    </li>
    @if($school?->usesParents())
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['parents', 'parent']) ? 'active' : '' }}">
        <a href="{{ url('/admin/parents') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['parents', 'parent'])) aria-current="page" @endif><span>Parents</span></a>
    </li>
    @endif
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['standardlinks', 'standardlink']) ? 'active' : '' }}">
        <a href="{{ url('/admin/standardlinks') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['standardlinks', 'standardlink'])) aria-current="page" @endif><span>{{ $school->classWord() }}</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['homeworks', 'homework']) ? 'active' : '' }}">
        <a href="{{ url('/admin/homeworks') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['homeworks', 'homework'])) aria-current="page" @endif><span>Homework</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'events' ? 'active' : '' }}">
        <a href="{{ url('admin/events') }}" class="ob-rail-link" @if(Request::segment(2) == 'events') aria-current="page" @endif><span>Calendar</span></a>
    </li>

    <li class="ob-rail-item {{ in_array(Request::segment(2), ['reports', 'report']) ? 'active' : '' }}">
        <a href="{{ url('/admin/reports') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['reports', 'report'])) aria-current="page" @endif><span>Reports</span></a>
    </li>
    <li class="ob-rail-item {{ $settingsActive ? 'active' : '' }}">
        <details class="ob-rail-fold" @if($settingsActive) open @endif>
            <summary class="ob-rail-link">Settings</summary>
            <ul class="ob-rail-sub">
                <li class="ob-rail-item {{ Request::segment(2) == 'schooldetails' ? 'active' : '' }}">
                    <a href="{{ url('/admin/schooldetails') }}" class="ob-rail-link" @if(Request::segment(2) == 'schooldetails') aria-current="page" @endif><span>{{ $school->orgWord() }}</span></a>
                </li>
                <li class="ob-rail-item {{ in_array(Request::segment(2), ['academics', 'academic']) ? 'active' : '' }}">
                    <a href="{{ url('admin/academics') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['academics', 'academic'])) aria-current="page" @endif><span>Academic years</span></a>
                </li>
                <li class="ob-rail-item {{ in_array(Request::segment(2), ['admissions', 'admission']) ? 'active' : '' }}">
                    <a href="{{ url('admin/admissions') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['admissions', 'admission'])) aria-current="page" @endif><span>Admissions</span></a>
                </li>
                <li class="ob-rail-item {{ in_array(Request::segment(2), ['promotions', 'promotion']) ? 'active' : '' }}">
                    <a href="{{ url('/admin/promotions') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['promotions', 'promotion'])) aria-current="page" @endif><span>Promotions</span></a>
                </li>
                <li class="ob-rail-item {{ Request::segment(2) == 'marks' ? 'active' : '' }}">
                    <a href="{{ url('/admin/marks') }}" class="ob-rail-link" @if(Request::segment(2) == 'marks') aria-current="page" @endif><span>Marks</span></a>
                </li>
            </ul>
        </details>
    </li>
</ul>
