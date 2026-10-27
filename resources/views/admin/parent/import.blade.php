@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Import parents</h1>
        <a href="{{ url('/admin/parents') }}">Back</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/parents/import/preview') }}" class="ob-form" enctype="multipart/form-data">
        @csrf
        <p><a href="{{ url('/admin/parents/import/template') }}">Download template</a></p>
        <label>Paste rows (parent name, email, phone, child admission no or name, relationship)</label>
        <textarea name="grid" rows="12"></textarea>
        <label>Or CSV</label>
        <input type="file" name="file" accept=".csv,.txt">
        <button class="crest-btn" type="submit">Preview</button>
    </form>
</div>
@endsection
