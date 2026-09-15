@extends('layouts.marketing')

@section('title', 'Crest platform')

@section('content')
<section class="crest-section">
    <h2>Schools on Crest</h2>
    @include('partials.message')
    <table class="crest-table">
        <thead>
            <tr><th>School</th><th>Slug</th><th>Plan</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($schools as $school)
                <tr>
                    <td>{{ $school->name }}</td>
                    <td>{{ $school->slug }}</td>
                    <td>{{ optional(optional($school->subscription()->latest('id')->first())->plan)->display_name ?? '—' }}</td>
                    <td>{{ $school->status ? 'Active' : 'Suspended' }}</td>
                    <td>
                        <form method="POST" action="{{ url('/platform/schools/'.$school->id.'/plan') }}" style="display:inline">
                            @csrf
                            <select name="plan">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->name }}">{{ $plan->display_name }}</option>
                                @endforeach
                            </select>
                            <button class="crest-btn" style="padding:6px 12px;">Set plan</button>
                        </form>
                        <form method="POST" action="{{ url('/platform/schools/'.$school->id.'/toggle') }}" style="display:inline">
                            @csrf
                            <button class="crest-btn crest-btn-ghost" style="padding:6px 12px;">{{ $school->status ? 'Suspend' : 'Activate' }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top:24px;">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('lo').submit();">Log out</a>
    </p>
    <form id="lo" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
</section>
@endsection
