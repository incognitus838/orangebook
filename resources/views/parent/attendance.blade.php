@extends('layouts.parent.layout')

@section('content')
<div class="">
    <h1 class="admin-h1 my-3">Attendance · {{ $child->name }}</h1>
    <p class="text-sm text-gray-600 mb-4">{{ $percent !== null ? $percent.'% present (including late)' : 'No attendance marked yet.' }}</p>
    <table class="w-full bg-white border text-sm">
        <tr class="text-left border-b"><th class="py-2 px-2">Date</th><th>Session</th><th>Mark</th><th>Remark</th></tr>
        @forelse($rows as $row)
            <tr class="border-b">
                <td class="py-2 px-2">{{ optional($row->date)->format('d M Y') }}</td>
                <td>{{ ucfirst($row->session) }}</td>
                <td>{{ $row->mark ?: ($row->status ? 'present' : 'absent') }}</td>
                <td>{{ $row->remarks }}</td>
            </tr>
        @empty
            <tr><td class="py-2 px-2" colspan="4">No records yet.</td></tr>
        @endforelse
    </table>
</div>
@endsection
