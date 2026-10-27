@extends('layouts.admin.layout')
@section('content')
@php $school = $school ?? Auth::user()->school; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">New intake</h1>
        <a href="{{ url('/admin/students') }}">Back</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/students/import/preview') }}" class="ob-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="mode" value="intake">
        <label>Entry level</label>
        <select name="level_id" required>
            @foreach($levels as $level)
                <option value="{{ $level->id }}">{{ $level->name }}</option>
            @endforeach
        </select>
        <label>Arm allocation</label>
        <select name="allocate">
            <option value="auto">Auto-balance</option>
            <option value="gender">Balance by gender</option>
            <option value="alpha">Alphabetical split</option>
        </select>
        <p class="ob-help">Approved applications for this level are added to the preview. Paste more rows or upload a CSV.</p>
        <label>Paste rows (first name, last name, gender, admission no, email, phone)</label>
        <textarea name="grid" rows="12"></textarea>
        <input type="file" name="file" accept=".csv,.txt,.xlsx">
        <button class="crest-btn" type="submit">Preview intake</button>
    </form>
</div>
@endsection
