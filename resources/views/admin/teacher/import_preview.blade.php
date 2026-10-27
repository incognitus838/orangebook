@extends('layouts.admin.layout')
@section('content')
@php $ready = collect($preview['rows'])->where('_ok', true)->count(); @endphp
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Preview invites</h1>
    </div>
    @include('partials.message')
    <p class="ob-help">{{ $ready }} ready, {{ count($preview['errors']) }} skipped.</p>
    <div class="ob-table-wrap">
        <table class="crest-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($preview['rows'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['email'] }}</td>
                        <td>{{ $row['role'] }}</td>
                        <td>{{ $row['_ok'] ? 'Invite' : $row['_error'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ url('/admin/teachers/import/confirm') }}">
        @csrf
        <button class="crest-btn" type="submit">Send {{ $ready }} invites</button>
    </form>
</div>
@endsection
