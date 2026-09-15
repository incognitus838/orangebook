@extends('layouts.student.layout')

@section('content')
<div class="">
    <h1 class="admin-h1 my-3">My results</h1>
    @if(!$termResult || !$termResult->published_at)
        <p class="text-gray-700">Your form teacher has not published this term’s results yet.</p>
    @else
        <p class="text-sm text-gray-600 mb-4">{{ $className }} · {{ $term->name }} {{ $year->name }}</p>
        <p class="mb-4">Average <strong>{{ $termResult->average }}</strong> · Position <strong>{{ $termResult->position }} / {{ $termResult->class_size }}</strong> · Grade <strong>{{ $termResult->grade }}</strong></p>
        <table class="w-full bg-white border text-sm mb-4">
            <tr class="text-left border-b"><th class="py-2 px-2">Subject</th><th>CA</th><th>Mid-term</th><th>Exam</th><th>Total</th><th>Grade</th><th>Remark</th></tr>
            @foreach($scores as $score)
                <tr class="border-b">
                    <td class="py-2 px-2">{{ $score->subject->name }}</td>
                    <td>{{ $score->ca }}</td>
                    <td>{{ $score->midterm }}</td>
                    <td>{{ $score->exam }}</td>
                    <td>{{ $score->total }}</td>
                    <td>{{ $score->grade }}</td>
                    <td>{{ $score->remark }}</td>
                </tr>
            @endforeach
        </table>
        <a href="{{ url('/student/results/pdf') }}" class="inline-block bg-gray-900 text-white text-sm px-4 py-2">Download report card (PDF)</a>
    @endif
</div>
@endsection
