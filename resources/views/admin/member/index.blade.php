@extends('layouts.admin.layout')
@section('content')
@php $school = optional(Auth::user()->school); @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $school->learnerWord() }} ({{ $students->total() }})</h1>
        <a class="crest-btn" href="{{ url('/admin/student/add') }}">Add {{ strtolower($school->learnerWordOne()) }}</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/students/bulk') }}">Bulk add</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/students/import') }}">Import CSV</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/students/intake') }}">New intake</a>
    </div>
    @include('partials.message')
    <form method="GET" action="{{ url('/admin/students') }}" class="ob-filters">
        <div>
            <label>{{ $school->classWordOne() }}</label>
            <select name="class_id">
                <option value="">All {{ strtolower($school->classWord()) }}</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->StandardSection }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Search</label>
            <input type="text" name="q" value="{{ $search }}" placeholder="Name, email, phone, admission no">
        </div>
        @if($school->usesParents())
        <div>
            <label><input type="checkbox" name="no_parent" value="1" @checked(!empty($noParent))> No parent</label>
        </div>
        @endif
        <button class="crest-btn ob-btn-secondary" type="submit">Show</button>
    </form>
    <div class="ob-table-wrap">
        <table class="crest-table ob-register">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Admission no</th>
                    <th>{{ $school->classWordOne() }}</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td><a href="{{ url('/admin/students/'.$student->id) }}">{{ $student->name }}</a></td>
                        <td>{{ $student->registration_number ?: '—' }}</td>
                        <td>{{ optional(optional($student->studentAcademicLatest)->standardLink)->StandardSection ?? '—' }}</td>
                        <td>{{ $student->email ?: '—' }}</td>
                        <td>{{ $student->mobile_no ?: '—' }}</td>
                        <td>@include('admin.partials.open_desk', ['user' => $student])</td>
                    </tr>
                @empty
                    <tr><td colspan="6">None yet. Add one, or import a list.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $students->links() }}
</div>
@endsection
