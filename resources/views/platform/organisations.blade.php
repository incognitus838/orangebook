@extends('layouts.platform')
@section('title', 'Organisations')
@section('content')
<h1 class="admin-h1">Organisations</h1>
<p class="ob-help">School, NGO programme, and training firm. Open puts you in as the principal.</p>
<div class="ob-table-wrap">
    <table class="crest-table">
        <thead>
            <tr><th>Name</th><th>Kind</th><th>Join code</th><th>Learners</th><th>Staff</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($schools as $school)
                <tr>
                    <td><a href="{{ url('/platform/organisations/'.$school->id) }}">{{ $school->name }}</a></td>
                    <td>{{ $school->kindLabel() }}</td>
                    <td>{{ $school->join_code ?: '—' }}</td>
                    <td>{{ $school->students_count }}</td>
                    <td>{{ $school->teachers_count }}</td>
                    <td>{{ $school->status ? 'Active' : 'Suspended' }}</td>
                    <td>
                        <form method="POST" action="{{ url('/platform/organisations/'.$school->id.'/open') }}" style="display:inline">@csrf
                            <button class="crest-btn" type="submit" style="padding:8px 12px;font-size:13px">Open</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
