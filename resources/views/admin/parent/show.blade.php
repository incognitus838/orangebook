@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $user->name }}</h1>
        @include('admin.partials.open_desk', ['user' => $user])
        <a href="{{ url('/admin/parents') }}">Back to parents</a>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/parent/show') }}/{{ rawurlencode($user->name) }}" class="ob-form">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')<p class="crest-err">{{ $message }}</p>@enderror
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->mobile_no) }}">
        <button class="crest-btn" type="submit" style="margin-top:28px">Save</button>
    </form>

    <div class="ob-form" style="margin-top:24px">
        <p class="ob-form-title">Children</p>
        @if($children->isEmpty())
            <p class="ob-help">No children linked yet.</p>
        @else
            <div class="ob-table-wrap" style="border:0;padding:0">
                <table class="crest-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>{{ optional(Auth::user()->school)->classWordOne() }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($children as $child)
                            <tr>
                                <td><a href="{{ url('/admin/students/'.$child->id) }}">{{ $child->name }}</a></td>
                                <td>{{ optional(optional($child->studentAcademicLatest)->standardLink)->StandardSection ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ url('/admin/parent/show') }}/{{ rawurlencode($user->name) }}/child/{{ $child->id }}/remove">
                                        @csrf
                                        <button type="submit" class="ob-chip-x" style="font-size:14px">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($students->isNotEmpty())
            <form method="POST" action="{{ url('/admin/parent/show') }}/{{ rawurlencode($user->name) }}/child" style="margin-top:16px">
                @csrf
                <label>Link a child</label>
                <div class="ob-form-row">
                    <select name="student_id" required>
                        <option value="">Choose student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                    <button class="crest-btn" type="submit" style="margin-top:0;align-self:end">Link</button>
                </div>
                @error('student_id')<p class="crest-err">{{ $message }}</p>@enderror
            </form>
        @endif
    </div>
</div>
@endsection
