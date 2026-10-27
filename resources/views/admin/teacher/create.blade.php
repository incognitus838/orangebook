@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Add {{ strtolower(optional(Auth::user()->school)->staffWordOne() ?? 'teacher') }}</h1>
        <a href="{{ url('/admin/teachers') }}">Back</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/teacher/add') }}" class="ob-form">
        @csrf
        <label for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required>
        @error('name')<p class="crest-err">{{ $message }}</p>@enderror
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        @error('email')<p class="crest-err">{{ $message }}</p>@enderror
        <label for="phone">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone') }}">
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="teaching">Teaching</option>
            <option value="nonteaching">Non-teaching</option>
        </select>
        <label for="class_id">Form {{ strtolower(optional(Auth::user()->school)->classWordOne() ?? 'class') }} (optional)</label>
        <select id="class_id" name="class_id">
            <option value="">None</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->StandardSection }}</option>
            @endforeach
        </select>
        <label for="subjects">Subjects (optional, comma separated)</label>
        <input id="subjects" type="text" name="subjects" value="{{ old('subjects') }}">
        <p class="ob-help">They get an invite. Orangebook does not set a password here.</p>
        <button class="crest-btn" type="submit" style="margin-top:28px">Send invite</button>
    </form>
</div>
@endsection
