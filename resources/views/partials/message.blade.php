@if (session('successmessage') || session('failmessage'))
    <p class="ob-flash {{ session('failmessage') ? 'is-bad' : '' }}">
        {{ session('successmessage') ?: session('failmessage') }}
    </p>
    @php
        session()->forget('successmessage');
        session()->forget('failmessage');
    @endphp
@elseif ($errors->any())
    <p class="ob-flash is-bad">{{ $errors->first() }}</p>
@endif     