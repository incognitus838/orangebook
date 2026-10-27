@extends('layouts.admin.layout')
@section('content')
@php $school = $school ?? Auth::user()->school; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $mode === 'csv' ? 'Import' : 'Bulk add' }} {{ strtolower($school->staffWord()) }}</h1>
        <a href="{{ url('/admin/teachers') }}">Back</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/teachers/import/preview') }}" class="ob-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        @if($mode === 'csv')
            <p><a href="{{ url('/admin/teachers/import/template') }}">Download template</a></p>
            <label>CSV</label>
            <input type="file" name="file" accept=".csv,.txt" required>
        @else
            <label>Paste rows (name, email, phone, role, subjects)</label>
            <textarea name="grid" rows="12" required placeholder="Bola Idris, bola@school.test, 0803..., teaching, English"></textarea>
        @endif
        <p class="ob-help">Each person gets an invite. Orangebook does not set a password here.</p>
        <button class="crest-btn" type="submit">Preview</button>
    </form>
</div>
@endsection
