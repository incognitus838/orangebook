@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Academic years</h1>
    </div>
    @include('partials.message')

    @if($currentYear && $terms->count())
        <div class="ob-record mb-6" style="max-width:none">
            <p class="ob-form-title">This term · {{ $currentYear->name }}</p>
            <div class="cal-switch" style="margin-top:8px">
                @foreach($terms as $term)
                    @if($term->is_current)
                        <span class="is-on" style="padding:8px 14px;border:1px solid var(--crest-ink);background:var(--crest-ink);color:#fff;font-size:14px">{{ $term->name }}</span>
                    @else
                        <form method="POST" action="{{ url('/admin/academics/term/'.$term->id.'/current') }}" style="display:inline">
                            @csrf
                            <button type="submit" style="padding:8px 14px;border:1px solid var(--crest-rule);background:#fff;font-size:14px;cursor:pointer">{{ $term->name }}</button>
                        </form>
                    @endif
                @endforeach
            </div>
            <p class="text-sm mt-3" style="color:var(--crest-muted)">Results, attendance and reports use the term that is on.</p>
        </div>
    @endif

    <form method="POST" action="{{ url('/admin/academics') }}" class="ob-form mb-6">
        @csrf
        <p class="ob-form-title">Add year</p>
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" placeholder="2026/2027" required>
        <label>Starts</label>
        <input type="date" name="start_date" value="{{ old('start_date') }}" required>
        <label>Ends</label>
        <input type="date" name="end_date" value="{{ old('end_date') }}" required>
        <label>Status</label>
        <select name="status" required>
            <option value="2">Next year</option>
            <option value="1">Current year</option>
            <option value="0">Past year</option>
        </select>
        <button class="crest-btn" type="submit" style="margin-top:24px">Add year</button>
    </form>
    <div class="ob-table-wrap">
        <table class="crest-table">
            <thead>
                <tr><th>Year</th><th>Starts</th><th>Ends</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($academicYears as $year)
                    <tr>
                        <td>{{ $year->name }}</td>
                        <td>{{ optional($year->start_date)->format('d M Y') }}</td>
                        <td>{{ optional($year->end_date)->format('d M Y') }}</td>
                        <td>{{ $year->status_display }}</td>
                        <td>
                            @if((int) $year->status !== 1)
                                <form method="POST" action="{{ url('/admin/academics/'.$year->id.'/current') }}">
                                    @csrf
                                    <button class="crest-btn" type="submit" style="padding:8px 14px;font-size:13px">Make current</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No academic years yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
