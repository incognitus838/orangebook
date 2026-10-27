@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Promotions</h1>
    </div>
    @include('partials.message')
    @if(!$year)
        <p class="bg-white border p-4">Set a current academic year first, under Academic years.</p>
    @else
        <p class="ob-help">At the end of the year, move every {{ strtolower(optional(Auth::user()->school)->learnerWordOne() ?? 'student') }} in one {{ strtolower(optional(Auth::user()->school)->classWordOne() ?? 'class') }} into the next. This cannot be undone from this screen.</p>
        <form method="POST" action="{{ url('/admin/promotions') }}" class="ob-form">
            @csrf
            <p class="ob-form-title">{{ $year->name }}</p>
            <label>Move {{ strtolower(optional(Auth::user()->school)->learnerWord() ?? 'students') }} from</label>
            <select name="from_class_id" required>
                <option value="">Choose class</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->StandardSection }} ({{ $counts[$class->id] ?? 0 }})</option>
                @endforeach
            </select>
            <label>Into</label>
            <select name="to_class_id" required>
                <option value="">Choose class</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->StandardSection }} ({{ $counts[$class->id] ?? 0 }})</option>
                @endforeach
            </select>
            <label class="ob-check" style="margin-top:20px">
                <input type="checkbox" name="confirm" value="1" required>
                <span>I have checked both {{ strtolower(optional(Auth::user()->school)->classWord() ?? 'classes') }}</span>
            </label>
            <button class="crest-btn" type="submit">Move {{ strtolower(optional(Auth::user()->school)->learnerWord() ?? 'students') }}</button>
        </form>
    @endif
</div>
@endsection
