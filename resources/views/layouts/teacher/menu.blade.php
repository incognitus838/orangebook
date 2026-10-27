<ul class="ob-rail-list">
    <li class="ob-rail-item {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
        <a href="{{ url('teacher/dashboard') }}" class="ob-rail-link" @if(Request::segment(2) == 'dashboard') aria-current="page" @endif><span>Dashboard</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'class' && Request::segment(4) != 'results' ? 'active' : '' }}">
        <a href="{{ url('teacher/class') }}" class="ob-rail-link" @if(Request::segment(2) == 'class' && Request::segment(4) != 'results') aria-current="page" @endif><span>My {{ optional(Auth::user()->school)->classWordOne() }}</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'results' || Request::segment(4) == 'results' ? 'active' : '' }}">
        <a href="{{ url('teacher/results') }}" class="ob-rail-link" @if(Request::segment(2) == 'results' || Request::segment(4) == 'results') aria-current="page" @endif><span>Results</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['announcements', 'mail', 'notices', 'notice']) ? 'active' : '' }}">
        <a href="{{ url('/teacher/announcements') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['announcements', 'mail', 'notices', 'notice'])) aria-current="page" @endif><span>Announcements</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['homeworks', 'homework']) ? 'active' : '' }}">
        <a href="{{ url('/teacher/homeworks') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['homeworks', 'homework'])) aria-current="page" @endif><span>Homework</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'events' ? 'active' : '' }}">
        <a href="{{ url('teacher/events') }}" class="ob-rail-link" @if(Request::segment(2) == 'events') aria-current="page" @endif><span>Calendar</span></a>
    </li>

</ul>
