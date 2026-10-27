@extends('layouts.marketing')

@section('title', 'Apply for Orangebook')

@section('content')
<section class="lp-section">
    @if(session('applied_email'))
        <h2 class="lp-h2">We have your application.</h2>
        <p class="lede">We will write to {{ session('applied_email') }} with a figure for the term. Nothing is billed until you accept.</p>
    @else
        <h2 class="lp-h2">Apply. We quote for your hall.</h2>
        <div class="lp-apply">
            <div class="lp-apply-copy">
                <p>School, NGO programme or training firm. One organisation, one bill, per term. The figure is written after we know how many learners you keep. Nothing is billed until you accept.</p>
            </div>
            @include('marketing._apply_form', ['submit' => 'Apply for a quote'])
        </div>
    @endif
</section>
@endsection
