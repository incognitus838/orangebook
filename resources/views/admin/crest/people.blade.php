@extends('layouts.admin.layout')
@section('content')
@php $who = $who ?? 'staff'; @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Invites</h1>
        @if(optional(Auth::user()->school)->usesParents())
        <div class="cal-switch">
            <a href="{{ url('/admin/people?who=staff') }}" class="{{ $who === 'staff' ? 'is-on' : '' }}">{{ Auth::user()->school->staffWordOne() }}</a>
            <a href="{{ url('/admin/people?who=parent') }}" class="{{ $who === 'parent' ? 'is-on' : '' }}">Parent</a>
        </div>
        @endif
    </div>
    @include('partials.message')

    @if($who === 'staff')
        <p class="ob-help">Send a {{ strtolower(optional(Auth::user()->school)->staffWordOne() ?? 'teacher') }} a link. They choose their own password.</p>
        <form method="POST" action="{{ url('/admin/people/staff') }}" class="ob-form mb-6">
            @csrf
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <button class="crest-btn" type="submit" style="margin-top:24px">Send invite</button>
        </form>
        <form method="POST" action="{{ url('/admin/people/staff/bulk') }}" class="ob-form mb-6">
            @csrf
            <p class="ob-form-title">Invite many {{ strtolower(optional(Auth::user()->school)->staffWord() ?? 'teachers') }}</p>
            <p class="ob-help">One line each: Name, email</p>
            <textarea name="list" rows="6" required placeholder="Adaeze Okonkwo, ada@school.ng"></textarea>
            <button class="crest-btn" type="submit" style="margin-top:20px">Send invites</button>
        </form>
        <div class="ob-table-wrap">
            <table class="crest-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Status</th><th>Link</th></tr>
                </thead>
                <tbody>
                    @forelse($invites as $invite)
                        <tr>
                            <td>{{ $invite->name }}</td>
                            <td>{{ $invite->email }}</td>
                            <td>{{ $invite->accepted_at ? 'Joined' : ($invite->isOpen() ? 'Waiting' : 'Expired') }}</td>
                            <td>
                                @if($invite->isOpen())
                                    <a href="{{ url('/staff/join/'.$invite->token) }}">Open link</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No invites yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p class="ob-help">Link a parent to one child. Their login password is password. They can also join themselves at {{ url('/join') }} with code {{ Auth::user()->school->join_code }}.</p>
        <form method="POST" action="{{ url('/admin/people/parent') }}" class="ob-form">
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
            <button class="crest-btn" type="submit" style="margin-top:24px">Link parent</button>
        </form>
        <form method="POST" action="{{ url('/admin/people/parent/bulk') }}" class="ob-form" style="margin-top:24px">
            @csrf
            <p class="ob-form-title">Link many parents</p>
            <p class="ob-help">Whole class: pick the class, then paste Name, email — one line for each student who has no parent, in class order. Or paste Parent name, email, Child name without a class.</p>
            <label>Class (optional)</label>
            <select name="class_id">
                <option value="">No class — match child names in the list</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->StandardSection }}</option>
                @endforeach
            </select>
            <label>List</label>
            <textarea name="list" rows="8" required placeholder="Funke Bello, funke@mail.com, Uche Abdullahi"></textarea>
            <button class="crest-btn" type="submit" style="margin-top:20px">Link parents</button>
        </form>
    @endif
</div>
@endsection
