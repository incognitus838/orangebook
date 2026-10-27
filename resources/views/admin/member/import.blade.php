@extends('layouts.admin.layout')
@section('content')
@php $school = $school ?? Auth::user()->school; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $mode === 'csv' ? 'Import' : 'Bulk add' }} {{ strtolower($school->learnerWord()) }}</h1>
        <a href="{{ url('/admin/students') }}">Back</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/students/import/preview') }}" class="ob-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        @if($mode !== 'intake')
            <label>{{ $school->classWordOne() }}</label>
            <select name="class_id" @required($mode === 'bulk')>
                <option value="">Choose {{ strtolower($school->classWordOne()) }}</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
                @endforeach
            </select>
        @endif
        @if($mode === 'csv')
            <p><a href="{{ url('/admin/students/import/template') }}">Download template</a></p>
            <label>CSV or XLSX</label>
            <input type="file" name="file" accept=".csv,.txt,.xlsx" required>
        @else
            <label>Paste rows (first name, last name, gender, admission no, email, phone{{ $school->usesParents() ? ', parent name, parent email, parent phone' : '' }})</label>
            <textarea name="grid" rows="12" required placeholder="Ada&#9;Okoro&#9;female"></textarea>
        @endif
        <button class="crest-btn" type="submit">Preview</button>
    </form>
</div>
@endsection
