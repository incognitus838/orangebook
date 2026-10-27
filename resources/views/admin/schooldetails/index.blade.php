@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ optional($school)->orgWord() ?? 'School' }}</h1>
    </div>
    @include('partials.message')
    <p class="ob-help">This name appears on report cards, circulars and the parent desk.</p>
    <form method="POST" action="{{ url('/admin/schooldetails') }}" class="ob-form mb-6">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $school->name) }}" required>
        @error('name')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $school->email) }}">
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $school->phone) }}">
        <label>Address</label>
        <input type="text" name="address" value="{{ old('address', $school->address) }}">
        <button class="crest-btn" type="submit" style="margin-top:28px">Save</button>
    </form>
    <div class="ob-record">
        <dt>Join code</dt>
        <dd class="ob-code">{{ $school->join_code ?: '—' }}</dd>
        <p class="ob-help" style="margin-top:8px">
            @if($school->usesParents())
                Give this code to families. Parents join at <a href="{{ url('/join') }}">{{ url('/join') }}</a> with the child’s name. Students open their desk at <a href="{{ url('/join/student') }}">{{ url('/join/student') }}</a> with their name and admission number.
            @else
                Give this code to {{ strtolower($school->learnerWord()) }}. They open their desk at <a href="{{ url('/join/student') }}">{{ url('/join/student') }}</a> with their name and admission number.
            @endif
        </p>
    </div>
</div>
@endsection
