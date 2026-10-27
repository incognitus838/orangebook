@extends('layouts.platform')
@section('title', 'Applications')
@section('content')
<h1 class="admin-h1">Applications</h1>
<p class="ob-help">Schools apply on the public site. Open organisation creates the hall and a principal login. Password is password.</p>
<div class="ob-table-wrap">
    <table class="crest-table">
        <thead>
            <tr><th>Organisation</th><th>Kind</th><th>Contact</th><th>Learners</th><th>Notes</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->school_name }}</td>
                    <td>{{ $application->kindLabel() }}</td>
                    <td>{{ $application->admin_name }}<br>{{ $application->email }}@if($application->phone)<br>{{ $application->phone }}@endif</td>
                    <td>{{ $application->student_count ? number_format($application->student_count) : '—' }}</td>
                    <td>{{ $application->notes ?: '—' }}</td>
                    <td>{{ $application->status }}</td>
                    <td>
                        @if($application->status === 'pending')
                            <form method="POST" action="{{ url('/platform/applications/'.$application->id.'/approve') }}">@csrf
                                <button class="crest-btn" type="submit" style="padding:8px 12px;font-size:13px">Open organisation</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No applications yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
