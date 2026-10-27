<ul class="ob-rail-list">
    <li class="ob-rail-item {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
        <a href="{{ url('/parent/dashboard') }}" class="ob-rail-link" @if(Request::segment(2) == 'dashboard') aria-current="page" @endif><span>My children</span></a>
    </li>
    <li class="ob-rail-item {{ Request::segment(2) == 'circulars' ? 'active' : '' }}">
        <a href="{{ url('/parent/circulars') }}" class="ob-rail-link" @if(Request::segment(2) == 'circulars') aria-current="page" @endif><span>Announcements</span></a>
    </li>
    @foreach(Auth::user()->childUsers as $ward)
        <li class="ob-rail-item {{ Request::is('parent/child/'.$ward->id) || Request::is('parent/child/'.$ward->id.'/*') ? 'active' : '' }}">
            <a href="{{ url('/parent/child/'.$ward->id) }}" class="ob-rail-link" @if(Request::is('parent/child/'.$ward->id) || Request::is('parent/child/'.$ward->id.'/*')) aria-current="page" @endif><span>{{ $ward->name }}</span></a>
        </li>
    @endforeach
</ul>
