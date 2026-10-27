@extends('layouts.admission')
@section('title', 'Admission. '.(($school->name ?? '') ?: 'Orangebook'))
@section('content')
    <header class="ob-adm-bar">
        @if($logo && !str_contains($logo, 'demologo'))
            <img src="{{ $logo }}" alt="" onerror="this.remove()">
        @endif
        <strong>{{ ($school->name ?? '') ?: 'Orangebook' }}</strong>
    </header>
    <main class="ob-adm">
        @if($admission_open['meta_value']=="1")
            <h1 class="admin-h1">Admission form</h1>
            <p class="ob-adm-sub">Fill each step. You can go back before you submit.</p>
            @include('partials.message')
            <form method="POST" action="" enctype="multipart/form-data" id="msform">
                @csrf
                <add-admission url="{{ url('/') }}" slug="{{ $slug }}"></add-admission>
                <div id="add_admissionform"></div>
            </form>
        @else
            <div class="ob-adm-closed">
                <p class="ob-kicker">Admission</p>
                <h1 class="admin-h1">{{ ($admission_open['meta_value'] ?? null) === "0" && !empty($closedetails['meta_value']) ? $closedetails['meta_value'] : 'Admission is not open yet.' }}</h1>
                <p class="ob-adm-sub">Check back later, or contact the school office for dates.</p>
                @if(!empty($school?->email))
                    <a class="crest-btn crest-btn-ghost" href="mailto:{{ $school->email }}">Email the school</a>
                @endif
            </div>
        @endif
    </main>
@endsection
