@extends('layouts.admin.layout')
@section('content')
@php $school = optional(Auth::user()->school); @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $user->name }}</h1>
        @include('admin.partials.open_desk', ['user' => $user])
        <a href="{{ url('/admin/students') }}">Back to {{ strtolower($school->learnerWord()) }}</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/students/'.$user->id) }}" class="ob-form">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<p class="crest-err">{{ $message }}</p>@enderror
        <label>{{ $school->classWordOne() }}</label>
        <select name="class_id" required>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((int) old('class_id', optional($user->studentAcademicLatest)->standardLink_id) === (int) $class->id)>{{ $class->StandardSection }}</option>
            @endforeach
        </select>
        @error('class_id')<p class="crest-err">{{ $message }}</p>@enderror
        @if(($school->kind ?? '') === 'training' && !empty($enrollments) && $enrollments->count())
            <p class="ob-help">Courses this year:
                @foreach($enrollments as $row)
                    <span class="ob-chip">{{ optional($row->standardLink)->StandardSection ?? '—' }}</span>
                @endforeach
            </p>
            <label>Also enroll in</label>
            <select name="add_class_id">
                <option value="">Leave as is</option>
                @foreach($classes as $class)
                    @if(!$enrollments->contains(fn ($row) => (int) $row->standardLink_id === (int) $class->id))
                        <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
                    @endif
                @endforeach
            </select>
        @endif
        <label>Admission number</label>
        <input type="text" name="admission_no" value="{{ old('admission_no', $user->registration_number) }}">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}">
        @error('email')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->mobile_no) }}">
        <button class="crest-btn" type="submit" style="margin-top:28px">Save</button>
    </form>

    @if($school->usesParents())
        <div class="ob-form" style="margin-top:24px">
            <p class="ob-form-title">Parents</p>
            @if($parents->isEmpty())
                <p class="ob-help">No parent linked yet.</p>
            @else
                <ul class="ob-people">
                    @foreach($parents as $parent)
                        <li><a href="{{ url('/admin/parent/show') }}/{{ rawurlencode($parent->name) }}">{{ $parent->name }}</a> · {{ $parent->email }}</li>
                    @endforeach
                </ul>
            @endif
            <p class="ob-help" style="margin-top:16px;margin-bottom:0">
                <a href="{{ url('/admin/parent/add') }}?ref_name={{ rawurlencode($user->name) }}">Add a parent</a> for this {{ strtolower($school->learnerWordOne()) }}.
            </p>
        </div>
    @endif
</div>
@endsection
