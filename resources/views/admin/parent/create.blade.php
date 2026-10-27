@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Add parent</h1>
        <a href="{{ url('/admin/parents') }}">Back to parents</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/parent/add') }}" class="ob-form">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required>
        @error('name')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        @error('email')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}">
        <label>Child</label>
        <select name="student_id" required>
            <option value="">Choose student</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected((string) old('student_id', $selectedId) === (string) $student->id)>{{ $student->name }}</option>
            @endforeach
        </select>
        @error('student_id')<p class="crest-err">{{ $message }}</p>@enderror
        <button class="crest-btn" type="submit" style="margin-top:28px">Add parent</button>
    </form>
</div>
@endsection
