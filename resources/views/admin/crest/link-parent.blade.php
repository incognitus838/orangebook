@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ optional(Auth::user()->school)->usesParents() ? 'Link parent' : 'Emergency contact' }}</h1>
        <a href="{{ url('/admin/parents') }}">Back to parents</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/link-parent') }}" class="ob-form">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}">
        <label>Child</label>
        <select name="student_id" required>
            <option value="">Choose student</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>
            @endforeach
        </select>
        <p class="text-sm" style="margin-top:16px;color:var(--crest-muted)">Login password is password. Parents can also join themselves at {{ url('/join') }} with the school code {{ Auth::user()->school->join_code }}.</p>
        <button class="crest-btn" type="submit" style="margin-top:24px">Link parent</button>
    </form>
</div>
@endsection
