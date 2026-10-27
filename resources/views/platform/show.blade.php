@extends('layouts.platform')
@section('title', $school->name)
@section('content')
<div class="ob-head">
    <h1 class="admin-h1">{{ $school->name }}</h1>
    <a href="{{ url('/platform/organisations') }}">All organisations</a>
</div>
<div class="day-stats">
    <div class="day-stat"><span class="day-stat-n">{{ $counts['learners'] }}</span><span class="day-stat-l">{{ $school->learnerWord() }}</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ $counts['teachers'] }}</span><span class="day-stat-l">{{ $school->staffWord() }}</span></div>
    <div class="day-stat"><span class="day-stat-n">{{ $counts['parents'] }}</span><span class="day-stat-l">Parents</span></div>
</div>
<dl class="ob-record mb-6">
    <dt>Kind</dt><dd>{{ $school->kindLabel() }}</dd>
    <dt>Join code</dt><dd class="ob-code">{{ $school->join_code ?: '—' }}</dd>
    <dt>Principal</dt><dd>{{ optional($principal)->name }} · {{ optional($principal)->email }}</dd>
    <dt>Status</dt><dd>{{ $school->status ? 'Active' : 'Suspended' }}</dd>
</dl>
<form method="POST" action="{{ url('/platform/organisations/'.$school->id.'/open') }}" style="display:inline">@csrf
    <button class="crest-btn" type="submit">Open as principal</button>
</form>
<form method="POST" action="{{ url('/platform/organisations/'.$school->id.'/toggle') }}" style="display:inline;margin-left:12px">@csrf
    <button class="crest-btn crest-btn-ghost" type="submit">{{ $school->status ? 'Suspend' : 'Activate' }}</button>
</form>
<div class="ob-table-wrap" style="margin-top:32px">
    <table class="crest-table">
        <thead><tr><th>Name</th><th>Role</th><th>Email</th></tr></thead>
        <tbody>
            @foreach($people as $person)
                <tr>
                    <td>{{ $person->name }}</td>
                    <td>{{ match((int)$person->usergroup_id) { 3 => 'Principal', 5 => $school->staffWord(), 6 => $school->learnerWord(), 7 => 'Parent', default => 'Staff' } }}</td>
                    <td>{{ $person->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
