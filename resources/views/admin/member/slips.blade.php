@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Login slips</h1>
        <a href="{{ url('/admin/students') }}">Back to {{ strtolower(optional($school)->learnerWord() ?? 'students') }}</a>
    </div>
    @include('partials.message')
    @if(empty($slips))
        <p class="ob-help">No slips in this session. PINs are shown only once, right after you add {{ strtolower(optional($school)->learnerWord() ?? 'learners') }}.</p>
    @else
        <p class="ob-help">Print this page now. The PIN is not stored in plain text after you leave.</p>
        <button class="crest-btn" type="button" onclick="window.print()">Print slips</button>
        <div class="ob-slip-sheet">
            @foreach($slips as $slip)
                <article class="ob-slip">
                    <p class="ob-slip-org">{{ $slip['org'] ?? optional($school)->name }}</p>
                    <h2>{{ $slip['name'] }}</h2>
                    <p>{{ $slip['class'] }}</p>
                    <p>Admission no <strong>{{ $slip['admission_no'] }}</strong></p>
                    <p>PIN <strong>{{ $slip['pin'] }}</strong></p>
                    @if(!empty($slip['school_code']))
                        <p>School code {{ $slip['school_code'] }}</p>
                    @endif
                    <p class="ob-help">Log in at this school’s Orangebook page with the admission number and PIN.</p>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
