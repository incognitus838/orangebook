<ul class="list-reset text-sm">
    <li class="py-3 px-3 hover:bg-teal-900 {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
        <a href="{{ url('/parent/dashboard') }}" class="flex items-center">
            <span class="mx-3 whitespace-no-wrap">My children</span>
        </a>
    </li>
    @foreach(Auth::user()->childUsers as $ward)
        <li class="py-3 px-3 hover:bg-teal-900 {{ (string) Request::segment(3) === (string) $ward->id ? 'active' : '' }}">
            <a href="{{ url('/parent/child/'.$ward->id) }}" class="flex items-center">
                <span class="mx-3 whitespace-no-wrap">{{ $ward->name }}</span>
            </a>
        </li>
    @endforeach
</ul>
