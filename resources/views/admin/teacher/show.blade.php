@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $user->name }}</h1>
        @include('admin.partials.open_desk', ['user' => $user])
        <a href="{{ url('/admin/teachers') }}">Back to {{ strtolower(optional(Auth::user()->school)->staffWord()) }}</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/teacher/show') }}/{{ rawurlencode($user->name) }}" class="ob-form">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Form class</label>
        <select name="class_id">
            @if(!$user->standardLink)
                <option value="">—</option>
            @endif
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((int) old('class_id', optional($user->standardLink)->id) === (int) $class->id)>{{ $class->StandardSection }}</option>
            @endforeach
        </select>
        <p class="ob-help">Form class is also set on Classes. Choose a class here to make this teacher the form teacher.</p>
        @error('class_id')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->mobile_no) }}">
        <button class="crest-btn" type="submit" style="margin-top:28px">Save</button>
    </form>

    @if($user->teacherlink && $user->teacherlink->isNotEmpty())
        <div class="ob-form" style="margin-top:24px">
            <p class="ob-form-title">Subjects</p>
            <p class="ob-help">Assigned on Classes.</p>
            <ul class="ob-people">
                @foreach($user->teacherlink as $link)
                    <li>{{ optional($link->subject)->name ?: 'Subject' }} · {{ optional($link->standardLink)->StandardSection ?: '—' }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
