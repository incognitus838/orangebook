@extends('layouts.marketing')

@section('title', 'Crest — results and attendance for Nigerian secondary schools')

@section('content')
<section class="crest-hero">
    <div>
        <h1>The school record every parent can open.</h1>
        <p>Crest is the register, mark book and report card for JSS and SS — one login for the principal, the form teacher, the student and the parent. Each school is its own tenant.</p>
        <div class="crest-hero-actions">
            <a class="crest-btn" href="{{ url('/start') }}">Start a 14-day trial</a>
            <a class="crest-btn crest-btn-ghost" href="{{ url('/login') }}">Log in to your school</a>
        </div>
    </div>
    <aside class="crest-register" aria-label="Sample first-term report">
        <header>
            <span>Crest Secondary School</span>
            <span>First Term 2025/2026</span>
        </header>
        <p style="margin:0 0 12px;font-size:15px;font-weight:500;">Uche Abdullahi · JSS2A</p>
        <table>
            <thead>
                <tr><th>Subject</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th></tr>
            </thead>
            <tbody>
                <tr><td>English</td><td>22.5</td><td>37.5</td><td>75</td><td>A1</td></tr>
                <tr><td>Mathematics</td><td>23.4</td><td>39</td><td>78</td><td>A1</td></tr>
                <tr><td>Basic Science</td><td>23.1</td><td>38.5</td><td>77</td><td>A1</td></tr>
                <tr><td>Civic Education</td><td>22.8</td><td>38</td><td>76</td><td>A1</td></tr>
            </tbody>
        </table>
        <p style="font-size:13px;margin:16px 0 20px;color:var(--crest-ink);">Average 76.6 · Position 3 of 10 · Attendance 80%</p>
    </aside>
</section>

<section class="crest-band">
    <div><strong>JSS1–SS3</strong><span>Classes and arms the way Nigerian schools actually run them</span></div>
    <div><strong>Three terms</strong><span>First, Second and Third, with a current term always in view</span></div>
    <div><strong>WAEC A1–F9</strong><span>CA, mid-term and exam, then position and remark</span></div>
    <div><strong>One tenant</strong><span>Your logo, colour and records. No other school can see them</span></div>
</section>

<section class="crest-section">
    <h2>What the school uses every week</h2>
    <dl class="crest-ledger">
        <dt>Form teacher</dt>
        <dd>Marks morning attendance, sets homework, enters CA, mid-term and exam, then publishes the class.</dd>
        <dt>Student</dt>
        <dd>Sees this term’s scores and downloads a report card the moment results are published.</dd>
        <dt>Parent</dt>
        <dd>Logs in and sees only their child: results, attendance and the teacher’s comment.</dd>
        <dt>Principal</dt>
        <dd>Invites teachers, links parents, sets branding and chooses Trial, Basic or Pro.</dd>
    </dl>
</section>

<section class="crest-section" id="plans">
    <h2>Plans for one school or a group</h2>
    <div class="crest-plans">
        @foreach($plans as $plan)
            <article class="crest-plan {{ $plan->name === 'pro' ? 'is-featured' : '' }}">
                <h3>{{ $plan->display_name }}</h3>
                <p class="price">
                    @if($plan->amount > 0)
                        ₦{{ number_format($plan->amount) }} <small>/ term</small>
                    @else
                        Free <small>{{ $plan->cycle }} days</small>
                    @endif
                </p>
                <ul>
                    <li>{{ $plan->no_of_members >= 999999 ? 'Unlimited students' : number_format($plan->no_of_members).' students' }}</li>
                    <li>Results, attendance, parent portal</li>
                    @if($plan->name === 'pro')
                        <li>Custom domain and full branding</li>
                    @elseif($plan->name === 'basic')
                        <li>Report cards as PDF</li>
                    @else
                        <li>Full product while you try it</li>
                    @endif
                </ul>
                <a class="crest-btn" href="{{ url('/start?plan='.$plan->name) }}">Choose {{ $plan->display_name }}</a>
            </article>
        @endforeach
    </div>
</section>
@endsection
