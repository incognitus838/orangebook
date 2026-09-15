@extends('layouts.teacher.layout')

@section('content')
<div class="">
    <h1 class="admin-h1 my-3">{{ $class->StandardSection }}</h1>
    @include('partials.message')

    <p class="text-sm text-gray-600 mb-4">
        {{ $isFormTeacher ? 'You are the form teacher.' : 'You teach this class.' }}
        @if($term) · {{ $term->name }} @endif
        @if($settings) · CA {{ $settings->ca_weight }}% / Exam {{ $settings->exam_weight }}% · Pass mark {{ $settings->pass_mark }} · WAEC A1–F9 @endif
    </p>

    <div class="flex flex-wrap gap-3 mb-6">
        <div class="bg-white border px-4 py-3">
            <p class="text-xs text-gray-500">Students</p>
            <p class="text-xl font-semibold">{{ $students->count() }}</p>
        </div>
        <div class="bg-white border px-4 py-3">
            <p class="text-xs text-gray-500">Today present</p>
            <p class="text-xl font-semibold">{{ $percent }}%</p>
        </div>
        <a href="{{ url('/teacher/homework/add?standardLink_id='.$class->id) }}" class="bg-indigo-700 text-white px-4 py-3 text-sm font-medium self-center">Give homework</a>
        <a href="{{ url('/teacher/class/'.$class->id.'/results') }}" class="bg-gray-900 text-white px-4 py-3 text-sm font-medium self-center">Enter results</a>
    </div>

    <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/attendance') }}" class="bg-white border p-4 mb-8">
        @csrf
        <div class="flex flex-wrap items-end gap-4 mb-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date</label>
                <input type="date" name="date" value="{{ $today }}" class="border px-2 py-1">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Session</label>
                <select name="session" class="border px-2 py-1">
                    <option value="forenoon">Morning</option>
                    <option value="afternoon">Afternoon</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-900 text-white text-sm px-4 py-2">Save attendance</button>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Student</th>
                    <th>Mark</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $row)
                    @php
                        $uid = $row->user_id;
                        $current = optional($marks->get($uid))->mark ?? 'present';
                    @endphp
                    <tr class="border-b">
                        <td class="py-2">{{ $row->user->name ?? 'Student' }}</td>
                        <td>
                            <select name="marks[{{ $uid }}]" class="border px-2 py-1">
                                @foreach(['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'] as $value => $label)
                                    <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="remarks[{{ $uid }}]" value="{{ optional($marks->get($uid))->remarks }}" class="border px-2 py-1 w-full" placeholder="Optional">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </form>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border p-4">
            <h2 class="font-semibold mb-3">Homework</h2>
            @forelse($homeworks as $work)
                <p class="text-sm border-b py-2">{!! $work->description !!}</p>
            @empty
                <p class="text-sm text-gray-500">No homework yet.</p>
            @endforelse
        </div>
        <div class="bg-white border p-4">
            <h2 class="font-semibold mb-3">Student remarks</h2>
            @foreach($students as $row)
                <div class="mb-4 border-b pb-3">
                    <p class="text-sm font-medium">{{ $row->user->name ?? 'Student' }}</p>
                    @foreach($remarks->get($row->user_id, collect())->take(2) as $note)
                        <p class="text-xs text-gray-600">{{ $note->notes }}</p>
                    @endforeach
                    <form method="POST" action="{{ url('/teacher/class/'.$class->id.'/remarks/'.$row->user_id) }}" class="mt-2 flex gap-2">
                        @csrf
                        <input type="text" name="notes" class="border px-2 py-1 flex-1 text-sm" placeholder="Add a remark" required>
                        <button class="text-sm bg-gray-200 px-3 py-1">Save</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
