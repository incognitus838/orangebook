@extends($role === 'teacher' ? 'layouts.teacher.layout' : 'layouts.admin.layout')

@section('content')
<div>
    <h1 class="admin-h1 my-3">Sent mail</h1>
    <p class="text-sm mb-4"><a href="{{ $role === 'teacher' ? url('/teacher/mail') : url('/admin/mail') }}">Write a circular</a></p>
    <table class="crest-table">
        <thead>
            <tr><th>When</th><th>To</th><th>Subject</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ optional($row->fired_at)->format('d M Y H:i') ?: $row->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $row->to }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>@php $st = strtolower((string) $row->status); @endphp{{ $st === 'failed' ? 'Not sent' : ($st === 'delivered' ? 'Sent' : ucfirst((string) $row->status)) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Nothing sent yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
