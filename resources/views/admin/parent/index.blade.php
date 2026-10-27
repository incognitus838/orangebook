@extends('layouts.admin.layout')
@section('content')
@php
    $count = isset($parents) && method_exists($parents, 'total') ? $parents->total() : (isset($parents) ? $parents->count() : 0);
    $qs = request()->query();
@endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Parents ({{ $count }})</h1>
        <a class="crest-btn" href="{{ url('/admin/parent/add') }}">Add parent</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/parents/import') }}">Import parents</a>
        <a class="crest-btn ob-btn-secondary" href="{{ url('/admin/parents?'.http_build_query($qs + ['export' => 'csv'])) }}">Download CSV</a>
    </div>
    @include('partials.message')
    <form method="GET" action="{{ url('/admin/parents') }}" class="ob-filters">
        <div>
            <label>{{ optional(Auth::user()->school)->classWordOne() }}</label>
            <select name="class_id">
                <option value="">All {{ strtolower(optional(Auth::user()->school)->classWord()) }}</option>
                @foreach($classes ?? [] as $class)
                    <option value="{{ $class->id }}" @selected((string) ($classId ?? '') === (string) $class->id)>{{ $class->StandardSection }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Level</label>
            <select name="standard_id">
                <option value="">All levels</option>
                @foreach($levels ?? [] as $level)
                    <option value="{{ $level->id }}" @selected((string) ($standardId ?? '') === (string) $level->id)>{{ $level->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Sort</label>
            <select name="sort">
                <option value="name" @selected(($sort ?? 'name') === 'name')>Name</option>
                <option value="class" @selected(($sort ?? '') === 'class')>{{ optional(Auth::user()->school)->classWordOne() }}</option>
                <option value="recent" @selected(($sort ?? '') === 'recent')>Recent</option>
            </select>
        </div>
        <div>
            <label>View</label>
            <select name="view">
                <option value="">List</option>
                <option value="grouped" @selected(!empty($grouped))>Grouped by {{ strtolower(optional(Auth::user()->school)->classWordOne()) }}</option>
            </select>
        </div>
        <div>
            <label>Search</label>
            <input type="text" name="q" value="{{ $search }}" placeholder="Name, email, phone">
        </div>
        <div>
            <label><input type="checkbox" name="no_child" value="1" @checked(!empty($noChild))> Parents with no linked child</label>
        </div>
        <button class="crest-btn" type="submit">Show</button>
        <a href="{{ url('/admin/students?no_parent=1') }}">Students with no parent</a>
    </form>

    @if(!empty($grouped))
        @foreach($groups as $group)
            <h2 class="admin-h2" style="margin-top:24px">{{ $group['class']->StandardSection }} ({{ $group['parents']->count() }})</h2>
            @include('admin.parent._table', ['parents' => $group['parents']])
        @endforeach
    @else
        @include('admin.parent._table', ['parents' => $parents])
        {{ method_exists($parents, 'links') ? $parents->links() : '' }}
    @endif
</div>
@endsection
