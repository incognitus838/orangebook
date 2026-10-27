@extends('layouts.platform')

@section('title', 'Orangebook platform')

@section('content')
<h1 class="admin-h1">Overview</h1>
<p class="ob-help">Every organisation on Orangebook. Fees are quoted per hall, one bill a term.</p>

<div class="day-stats">
    <div class="day-stat"><span class="day-stat-n">{{ $stats['orgs'] }}</span><span class="day-stat-l">Organisations</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ $stats['schools'] }}</span><span class="day-stat-l">Schools</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ $stats['ngo'] }}</span><span class="day-stat-l">NGO / programme</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ $stats['training'] }}</span><span class="day-stat-l">Training firms</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ number_format($stats['learners']) }}</span><span class="day-stat-l">Learners</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ $stats['pending'] }}</span><span class="day-stat-l">Applications waiting</span></div>
</div>

<h2 class="admin-h1" style="margin-top:32px">Organisations</h2>
<div class="ob-table-wrap mb-6">
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
                        <form method="POST" action="{{ url('/platform/organisations/'.$school->id.'/open') }}" style="display:inline">
                            @csrf
                            <button class="crest-btn" type="submit" style="padding:8px 12px;font-size:13px">Open</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2 class="admin-h1">Applications</h2>
<div class="ob-table-wrap">
    <table class="crest-table">
        <thead>
            <tr><th>Organisation</th><th>Kind</th><th>Contact</th><th>Learners</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->school_name }}</td>
                    <td>{{ $application->kindLabel() }}</td>
                    <td>{{ $application->admin_name }}<br>{{ $application->email }}</td>
                    <td>{{ $application->student_count ? number_format($application->student_count) : '—' }}</td>
                    <td>{{ $application->status }}</td>
                    <td>
                        @if($application->status === 'pending')
                            <form method="POST" action="{{ url('/platform/applications/'.$application->id.'/approve') }}">
                                @csrf
                                <button class="crest-btn" type="submit" style="padding:8px 12px;font-size:13px">Open organisation</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No applications yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
