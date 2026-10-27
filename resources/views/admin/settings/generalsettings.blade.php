@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">General</h1>
    </div>
    @include('partials.message')
    @if(!$year || !$settings)
        <p class="bg-white border p-4">Set a current academic year first.</p>
    @else
        <form method="POST" action="{{ url('/admin/settings/generalsettings') }}" class="ob-form">
            @csrf
            <p class="ob-form-title">Marks · {{ $year->name }}</p>
            <label>Pass mark</label>
            <input type="number" name="pass_mark" min="0" max="100" value="{{ old('pass_mark', $settings->pass_mark) }}" required>
            <label>CA out of</label>
            <input type="number" name="ca_max" min="0" max="100" value="{{ old('ca_max', $settings->ca_max) }}" required>
            <label>Mid-term out of</label>
            <input type="number" name="midterm_max" min="0" max="100" value="{{ old('midterm_max', $settings->midterm_max) }}" required>
            <label>Exam out of</label>
            <input type="number" name="exam_max" min="0" max="100" value="{{ old('exam_max', $settings->exam_max) }}" required>
            <button class="crest-btn" type="submit" style="margin-top:28px">Save</button>
        </form>
    @endif
</div>
@endsection
