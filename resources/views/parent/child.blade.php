@extends('layouts.parent.layout')

@section('content')
<div class="">
    <h1 class="admin-h1 my-3">{{ $child->name }}</h1>
    <p class="text-sm text-gray-600 mb-4">{{ $report['className'] }} · {{ optional($report['term'])->name }} {{ optional($report['year'])->name }}</p>

    <div class="flex flex-wrap gap-3 mb-6">
        <div class="bg-white border px-4 py-3">
            <p class="text-xs text-gray-500">Average</p>
            <p class="text-xl font-semibold">{{ optional($report['termResult'])->average ?? '—' }}</p>
        </div>
        <div class="bg-white border px-4 py-3">
            <p class="text-xs text-gray-500">Position</p>
            <p class="text-xl font-semibold">
                @if($report['termResult']) {{ $report['termResult']->position }} / {{ $report['termResult']->class_size }} @else — @endif
            </p>
        </div>
        <div class="bg-white border px-4 py-3">
            <p class="text-xs text-gray-500">Attendance</p>
            <p class="text-xl font-semibold">{{ $report['attendancePercent'] !== null ? $report['attendancePercent'].'%' : '—' }}</p>
        </div>
    </div>

    <p class="mb-4">
        <a href="{{ url('/parent/child/'.$child->id.'/results') }}" class="text-sm text-indigo-700 font-medium mr-4">Results</a>
        <a href="{{ url('/parent/child/'.$child->id.'/attendance') }}" class="text-sm text-indigo-700 font-medium">Attendance</a>
    </p>

    <div class="bg-white border p-4 mb-6">
        <h2 class="font-semibold mb-2">Latest results</h2>
        @if(!$report['termResult'] || !$report['termResult']->published_at)
            <p class="text-sm text-gray-600">The form teacher has not published this term yet.</p>
        @else
            <table class="w-full text-sm">
                <tr class="text-left border-b"><th class="py-1">Subject</th><th>Total</th><th>Grade</th><th>Remark</th></tr>
                @foreach($report['scores'] as $score)
                    <tr class="border-b">
                        <td class="py-1">{{ $score->subject->name }}</td>
                        <td>{{ $score->total }}</td>
                        <td>{{ $score->grade }}</td>
                        <td>{{ $score->remark }}</td>
                    </tr>
                @endforeach
            </table>
            <a href="{{ url('/parent/child/'.$child->id.'/results/pdf') }}" class="inline-block mt-3 text-sm bg-gray-900 text-white px-3 py-2">Download report card</a>
        @endif
    </div>

    <div class="bg-white border p-4">
        <h2 class="font-semibold mb-2">Teacher comments</h2>
        @forelse($remarks as $note)
            <p class="text-sm border-b py-2">{{ $note->notes }} <span class="text-xs text-gray-500">{{ $note->created_at->format('d M Y') }}</span></p>
        @empty
            <p class="text-sm text-gray-500">No comments yet.</p>
        @endforelse
    </div>
</div>
@endsection
