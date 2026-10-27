@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Marks</h1>
    </div>
    @include('partials.message')
    @if(!$year || !$settings)
        <p class="bg-white border p-4">Set a current academic year first, under Academic years.</p>
    @else
        <p class="ob-help">{{ optional(Auth::user()->school)->staffWord() }} enter three scores for each subject: classwork, mid-term and the exam. A {{ strtolower(optional(Auth::user()->school)->learnerWordOne() ?? 'student') }} passes if the total is at least the pass mark.</p>
        <form method="POST" action="{{ url('/admin/marks') }}" class="ob-form">
            @csrf
            <p class="ob-form-title">{{ $year->name }}</p>
            <label>Pass mark (out of 100)</label>
            <input type="number" name="pass_mark" min="0" max="100" value="{{ old('pass_mark', $settings->pass_mark) }}" required>
            <label>Classwork is out of</label>
            <input type="number" name="ca_max" min="0" max="100" value="{{ old('ca_max', $settings->ca_max) }}" required>
            <label>Mid-term test is out of</label>
            <input type="number" name="midterm_max" min="0" max="100" value="{{ old('midterm_max', $settings->midterm_max) }}" required>
            <label>Exam is out of</label>
            <input type="number" name="exam_max" min="0" max="100" value="{{ old('exam_max', $settings->exam_max) }}" required>
            <p class="ob-help" style="margin-top:16px">Usual split: classwork 30, mid-term 20, exam 50. Together they make 100.</p>
            <button class="crest-btn" type="submit" style="margin-top:12px">Save</button>
        </form>
    @endif
</div>
@endsection
