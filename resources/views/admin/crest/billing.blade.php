@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Plan</h1>
    </div>
    @include('partials.message')
    <p class="ob-help">One organisation, one bill, each term. Orangebook writes a figure after we know how many learners you keep.</p>
    <dl class="ob-record">
        <dt>Organisation</dt>
        <dd>{{ $school->name }}</dd>
        <dt>Learners</dt>
        <dd>{{ number_format($studentCount) }}</dd>
    </dl>
</div>
@endsection
