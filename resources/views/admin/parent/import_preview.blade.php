@extends('layouts.admin.layout')
@section('content')
@php $ready = collect($rows)->where('_ok', true)->count(); @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Preview parents</h1>
    </div>
    @include('partials.message')
    <p class="ob-help">{{ $ready }} ready.</p>
    <div class="ob-table-wrap">
        <table class="crest-table">
            <thead><tr><th>Parent</th><th>Email</th><th>Child</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['parent_name'] }}</td>
                        <td>{{ $row['parent_email'] }}</td>
                        <td>{{ $row['child'] }}</td>
                        <td>{{ $row['_ok'] ? 'Ready' : $row['_error'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ url('/admin/parents/import/confirm') }}">
        @csrf
        <button class="crest-btn" type="submit">Link {{ $ready }}</button>
    </form>
</div>
@endsection
