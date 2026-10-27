@extends('layouts.admin.layout')
@section('content')
@php $school = $school ?? Auth::user()->school; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Add {{ strtolower($school->learnerWordOne()) }}</h1>
        <a href="{{ url('/admin/students') }}">Back to {{ strtolower($school->learnerWord()) }}</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/student/add') }}" class="ob-form">
        @csrf
        <label for="first_name">First name</label>
        <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required>
        @error('first_name')<p class="crest-err">{{ $message }}</p>@enderror
        <label for="last_name">Last name</label>
        <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}">
        <label for="other_names">Other names</label>
        <input id="other_names" type="text" name="other_names" value="{{ old('other_names') }}">
        <label for="gender">Gender</label>
        <select id="gender" name="gender">
            <option value="">Optional</option>
            <option value="male" @selected(old('gender') === 'male')>Male</option>
            <option value="female" @selected(old('gender') === 'female')>Female</option>
        </select>
        <label for="date_of_birth">Date of birth</label>
        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
        <label for="class_id">{{ $school->classWordOne() }}</label>
        <select id="class_id" name="class_id" required>
            <option value="">Choose {{ strtolower($school->classWordOne()) }}</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->StandardSection }}</option>
            @endforeach
        </select>
        @if(($school->kind ?? '') === 'training')
            <p class="ob-help">If this learner already exists (same email or admission number), they are enrolled in this course as well.</p>
        @endif
        @error('class_id')<p class="crest-err">{{ $message }}</p>@enderror
        <label for="admission_no">Admission number</label>
        <input id="admission_no" type="text" name="admission_no" value="{{ old('admission_no') }}" placeholder="Leave blank to auto-assign">
        @error('admission_no')<p class="crest-err">{{ $message }}</p>@enderror
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}">
        <p class="ob-help">Optional. Without email they log in with admission number and PIN.</p>
        @error('email')<p class="crest-err">{{ $message }}</p>@enderror
        <label for="phone">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone') }}">
        @if($school->usesParents())
            <p class="ob-form-title">Parent</p>
            <label for="parent_name">Parent name</label>
            <input id="parent_name" type="text" name="parent_name" value="{{ old('parent_name') }}">
            <label for="parent_email">Parent email</label>
            <input id="parent_email" type="email" name="parent_email" value="{{ old('parent_email') }}">
            <label for="parent_phone">Parent phone</label>
            <input id="parent_phone" type="text" name="parent_phone" value="{{ old('parent_phone') }}">
            <label for="relationship">Relationship</label>
            <input id="relationship" type="text" name="relationship" value="{{ old('relationship', 'parent') }}">
        @endif
        <button class="crest-btn" type="submit">Add {{ strtolower($school->learnerWordOne()) }}</button>
    </form>
</div>
@endsection
