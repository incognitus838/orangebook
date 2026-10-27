@extends('layouts.admin.layout')
@section('content')
@php
    $ready = collect($preview['rows'])->where('_ok', true)->count();
    $bad = collect($preview['rows'])->where('_ok', false)->count();
    $arms = $arms ?? collect();
@endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Preview import</h1>
        <a href="{{ url('/admin/students') }}">Back</a>
    </div>
    @include('partials.message')
    <p class="ob-help">{{ $ready }} ready, {{ $bad }} with problems.{{ $bad ? ' Download the error list, fix those rows, and paste again.' : '' }}</p>
    @if($bad)
        <p><a href="{{ url('/admin/students/import/errors') }}">Download errors CSV</a></p>
    @endif
    <form method="POST" action="{{ url('/admin/students/import/confirm') }}">
        @csrf
        <div class="ob-table-wrap">
            <table class="crest-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Admission</th>
                        <th>{{ optional(Auth::user()->school)->classWordOne() }}</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['rows'] as $i => $row)
                        <tr>
                            <td>{{ $row['first_name'] }} {{ $row['last_name'] }}</td>
                            <td>{{ $row['gender'] ?: '—' }}</td>
                            <td>{{ $row['admission_no'] ?: 'auto' }}</td>
                            <td>
                                @if($arms->isNotEmpty())
                                    <select name="class_id[{{ $i }}]">
                                        @foreach($arms as $arm)
                                            <option value="{{ $arm->id }}" @selected((string) ($row['_class_id'] ?? '') === (string) $arm->id)>{{ $arm->StandardSection }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $row['_class_label'] ?? ($row['class'] ?? '—') }}
                                @endif
                            </td>
                            <td>{{ $row['_ok'] ? 'Ready' : $row['_error'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button class="crest-btn" type="submit">Confirm {{ $ready }}</button>
    </form>
</div>
@endsection
