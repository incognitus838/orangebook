@extends('layouts.admin.layout')
@section('content')
@php $school = optional(Auth::user()->school); @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">{{ $school->staffWord() }} ({{ $teachers->count() }})</h1>
        <a class="crest-btn" href="{{ url('/admin/teacher/add') }}">Add {{ strtolower($school->staffWordOne()) }}</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/teachers/bulk') }}">Bulk add</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/teachers/import') }}">Import CSV</a>
    </div>
    @include('partials.message')
    <form method="GET" action="{{ url('/admin/teachers') }}" class="ob-filters">
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
            <label>Role</label>
            <select name="role">
                <option value="">All</option>
                <option value="teaching" @selected(($role ?? '') === 'teaching')>Teaching</option>
                <option value="nonteaching" @selected(($role ?? '') === 'nonteaching')>Non-teaching</option>
            </select>
        </div>
        <div>
            <label>Search</label>
            <input type="text" name="q" value="{{ $search }}" placeholder="Name, email, phone">
        </div>
        <button class="crest-btn" type="submit">Show</button>
    </form>
    <div class="ob-table-wrap">
        <table class="crest-table ob-register">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>{{ $school->classWordOne() }}</th>
                    <th class="ob-subjects">Subjects</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                    @php
                        $form = optional($teacher->standardLink)->StandardSection;
                        $links = $year
                            ? $teacher->teacherlink->where('academic_year_id', $year->id)
                            : $teacher->teacherlink;
                        $taught = $links
                            ->map(fn ($link) => optional($link->standardLink)->StandardSection)
                            ->filter()
                            ->unique()
                            ->values();
                        $classLabel = $form ?: $taught->implode(', ');
                        $subjects = $links->map(fn ($link) => optional($link->subject)->name)->filter()->unique()->values();
                    @endphp
                    <tr>
                        <td><a href="{{ url('/admin/teacher/show') }}/{{ rawurlencode($teacher->name) }}">{{ $teacher->name }}</a></td>
                        <td>{{ $classLabel !== '' ? $classLabel : '—' }}</td>
                        <td class="ob-subjects">
                            <div class="ob-chip-row">
                                @forelse($subjects as $subject)
                                    <span class="ob-chip">{{ $subject }}</span>
                                @empty
                                    —
                                @endforelse
                            </div>
                        </td>
                        <td>{{ $teacher->email }}</td>
                        <td>{{ $teacher->mobile_no ?: '—' }}</td>
                        <td>@include('admin.partials.open_desk', ['user' => $teacher])</td>
                    </tr>
                @empty
                    <tr><td colspan="6">None yet. Invite one with name and email.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
