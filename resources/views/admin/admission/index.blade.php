@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Admissions</h1>
    </div>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/admissions') }}" class="ob-form mb-6">
        @csrf
        <p class="ob-form-title">Add application</p>
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required>
        <label>Class</label>
        <select name="class_id" required>
            <option value="">Choose class</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->StandardSection }}</option>
            @endforeach
        </select>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}">
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}">
        <button class="crest-btn" type="submit" style="margin-top:24px">Add application</button>
    </form>
    <div class="ob-table-wrap">
        <table class="crest-table">
            <thead>
                <tr><th>Name</th><th>Class</th><th>Phone</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($admissions as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ optional($classes->first(fn ($c) => (int) $c->standard_id === (int) $row->standard_id && (int) $c->section_id === (int) $row->section_id))->StandardSection ?? (optional($row->standard)->name ?: '—') }}</td>
                        <td>{{ $row->father_mobile_no !== '-' ? $row->father_mobile_no : '—' }}</td>
                        <td>{{ $row->application_status }}</td>
                        <td>
                            @if($row->application_status === 'Pending' || $row->application_status === 'Draft')
                                <form method="POST" action="{{ url('/admin/admissions/'.$row->id.'/approve') }}">
                                    @csrf
                                    <button class="crest-btn" type="submit" style="padding:8px 14px;font-size:13px">Admit</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
