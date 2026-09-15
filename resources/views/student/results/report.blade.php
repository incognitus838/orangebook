<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report card</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1b2a4a; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 14px; margin: 4px 0 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #c9c1a8; padding: 6px; text-align: left; }
        th { background: #f4efe4; }
        .meta { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $school->name }}</h1>
    <h2>Term report card</h2>
    <p class="meta">
        Student: <strong>{{ $student->name }}</strong><br>
        Class: {{ $className }} · {{ $term->name }} · Session {{ $year->name }}
        @if($attendancePercent !== null)<br>Attendance: {{ $attendancePercent }}%@endif
    </p>
    <table>
        <tr>
            <th>Subject</th><th>CA</th><th>Mid-term</th><th>Exam</th><th>Total</th><th>Grade</th><th>Remark</th>
        </tr>
        @foreach($scores as $score)
            <tr>
                <td>{{ $score->subject->name }}</td>
                <td>{{ $score->ca }}</td>
                <td>{{ $score->midterm }}</td>
                <td>{{ $score->exam }}</td>
                <td>{{ $score->total }}</td>
                <td>{{ $score->grade }}</td>
                <td>{{ $score->remark }}</td>
            </tr>
        @endforeach
    </table>
    <p class="meta">
        Average: <strong>{{ $termResult->average }}</strong> &nbsp;
        Position: <strong>{{ $termResult->position }} / {{ $termResult->class_size }}</strong> &nbsp;
        Overall grade: <strong>{{ $termResult->grade }}</strong>
    </p>
    <p>Grading: A1 75–100 Excellent · B2 70–74 Very Good · B3 65–69 Good · C4–C6 Credit · D7–E8 Pass · F9 Fail. Pass mark 40.</p>
    <p>Form teacher: {{ optional($formTeacher)->name }}</p>
</body>
</html>
