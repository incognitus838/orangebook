<ul class="ob-rail-list">
    <li class="ob-rail-item {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
        <a href="{{ url('student/dashboard') }}" class="ob-rail-link" @if(Request::segment(2) == 'dashboard') aria-current="page" @endif><span>Dashboard</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'results' ? 'active' : '' }}">
        <a href="{{ url('student/results') }}" class="ob-rail-link" @if(Request::segment(2) == 'results') aria-current="page" @endif><span>Results</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['homework', 'homeworks']) ? 'active' : '' }}">
        <a href="{{ url('student/homeworks') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['homework', 'homeworks'])) aria-current="page" @endif><span>Homework</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'events' ? 'active' : '' }}">
        <a href="{{ url('student/events') }}" class="ob-rail-link" @if(Request::segment(2) == 'events') aria-current="page" @endif><span>Calendar</span></a>
    </li>
    <li class="ob-rail-item {{ in_array(Request::segment(2), ['notices', 'notice']) ? 'active' : '' }}">
        <a href="{{ url('/student/notices') }}" class="ob-rail-link" @if(in_array(Request::segment(2), ['notices', 'notice'])) aria-current="page" @endif><span>Announcements</span></a>
    </li>
</ul>
