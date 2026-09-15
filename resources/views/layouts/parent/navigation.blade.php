<nav class="navbar bg-white w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center">
  <div class="nav-brand flex items-center">
    <a class="crest-wordmark" href="{{ url('/parent/dashboard') }}" style="letter-spacing:0.08em;font-size:16px;">
      {{ ucwords(optional(Auth::user()->school)->name ?? 'Crest') }}
    </a>
  </div>
  <div class="flex items-center text-sm">
    <span class="mr-4 text-gray-600">{{ Auth::user()->name }}</span>
    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
  </div>
</nav>
