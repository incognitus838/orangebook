@extends('layouts.marketing')

@section('title', 'Orangebook. The mark book for every school')
@section('meta', 'Apply for Orangebook. Attendance, WAEC results and circulars for Nigerian schools, NGO programmes and training halls. Custom quote. One bill per term.')

@php
    $ic = function ($d) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    };
    $check = $ic('<path d="M20 6 9 17l-5-5"/>');
    $arrow = $ic('<path d="M5 12h14M13 5l7 7-7 7"/>');
@endphp

@section('content')
{{-- ============ HERO ============ --}}
<section class="sx-hero">
    <div class="sx-hero-bg" aria-hidden="true"></div>
    <div class="sx-wrap sx-hero-copy">
        <a class="sx-pill" href="#results">
            <span class="sx-pill-tag">New</span>
            WAEC report cards, A1 to F9, in one publish
            {!! $arrow !!}
        </a>
        <h1 class="sx-display">One login for the <span class="sx-grad-text">whole school.</span></h1>
        <p class="sx-lede">Attendance, WAEC results and circulars for secondary schools, NGO programmes and training halls. The principal, the teachers, the learners and the parents share one book.</p>
        <div class="sx-actions">
            <a class="lp-btn sx-btn-lg" href="#apply">Apply for a quote {!! $arrow !!}</a>
            <a class="sx-btn-ghost sx-btn sx-btn-lg" href="{{ url('/login') }}">Log in</a>
        </div>
        <ul class="sx-ticks">
            <li>{!! $check !!} Custom quote per term</li>
            <li>{!! $check !!} Nothing billed until you accept</li>
            <li>{!! $check !!} Parents see only their child</li>
        </ul>
    </div>

    <div class="sx-wrap sx-hero-stage">
        <div class="sx-window">
            <div class="sx-window-bar" aria-hidden="true">
                <span></span><span></span><span></span>
                <div class="sx-window-url">orangebook.ng/admin/dashboard</div>
            </div>
            <picture>
                <source type="image/webp" srcset="{{ asset('images/marketing/desk.webp') }}">
                <img src="{{ asset('images/marketing/desk.jpg') }}" width="1920" height="1200" alt="Orangebook principal dashboard for Ikeja Secondary School, with 83 learners, 20 teachers and class strength.">
            </picture>
        </div>
        <div class="sx-float sx-float-a" aria-hidden="true">
            <span class="sx-float-ic is-green">{!! $check !!}</span>
            <div><strong>Register taken</strong><small>JSS2A · 7:52 am</small></div>
        </div>
        <div class="sx-float sx-float-b" aria-hidden="true">
            <span class="sx-float-ic is-orange">{!! $ic('<circle cx="12" cy="8" r="6"/><path d="M15.5 12.9 17 22l-5-3-5 3 1.5-9.1"/>') !!}</span>
            <div><strong>First Term published</strong><small>48 report cards</small></div>
        </div>
    </div>
</section>

{{-- ============ AUDIENCE STRIP ============ --}}
<section class="sx-strip" aria-label="Who it is for">
    <div class="sx-wrap">
        <p>Built for every kind of hall</p>
        <ul>
            <li>{!! $ic('<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>') !!} Secondary schools</li>
            <li>{!! $ic('<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>') !!} Primary schools</li>
            <li>{!! $ic('<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>') !!} NGO programmes</li>
            <li>{!! $ic('<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') !!} Training firms</li>
        </ul>
    </div>
</section>

{{-- ============ FEATURES ============ --}}
<section class="sx-section" id="features">
    <div class="sx-wrap">
        <header class="sx-head">
            <p class="sx-eyebrow">Everything in one book</p>
            <h2 class="sx-h2">Run the whole term from one dashboard.</h2>
            <p class="sx-sub">Every desk gets exactly what it needs: the principal sees the hall, teachers see their class, families see their child.</p>
        </header>
        <div class="sx-grid">
            <article class="sx-feature">
                <span class="sx-feature-ic is-green">{!! $ic('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10.01-3-3"/>') !!}</span>
                <h3>Daily attendance</h3>
                <p>Present, late, absent and excused, marked by the form teacher before first lesson.</p>
            </article>
            <article class="sx-feature">
                <span class="sx-feature-ic is-orange">{!! $ic('<circle cx="12" cy="8" r="6"/><path d="M15.5 12.9 17 22l-5-3-5 3 1.5-9.1"/>') !!}</span>
                <h3>WAEC-style results</h3>
                <p>CA, mid term and exam, graded A1 to F9 with class position. Publish the term in one click.</p>
            </article>
            <article class="sx-feature">
                <span class="sx-feature-ic is-violet">{!! $ic('<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>') !!}</span>
                <h3>Parent access</h3>
                <p>Families join with a school code and the child’s name, and see only that child.</p>
            </article>
            <article class="sx-feature">
                <span class="sx-feature-ic is-blue">{!! $ic('<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>') !!}</span>
                <h3>Circulars &amp; notices</h3>
                <p>Send circulars to staff and families, and keep the notice board current for everyone.</p>
            </article>
            <article class="sx-feature">
                <span class="sx-feature-ic is-amber">{!! $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>') !!}</span>
                <h3>Calendar &amp; homework</h3>
                <p>Term dates, events and homework due, visible to the people who need them.</p>
            </article>
            <article class="sx-feature">
                <span class="sx-feature-ic is-slate">{!! $ic('<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>') !!}</span>
                <h3>Roles &amp; privacy</h3>
                <p>Principal, teacher, learner and parent desks. Each school’s records stay in its own book.</p>
            </article>
        </div>
    </div>
</section>

{{-- ============ DEEP DIVES ============ --}}
<section class="sx-split" id="attendance">
    <div class="sx-wrap sx-split-inner">
        <div class="sx-split-copy">
            <p class="sx-eyebrow">Attendance</p>
            <h2 class="sx-h2">Attendance before first lesson.</h2>
            <p class="sx-sub">Form teachers mark present, late, absent and excused. The hall sees who is in.</p>
            <ul class="sx-list">
                <li>{!! $check !!} Four marks, one tap each</li>
                <li>{!! $check !!} Today’s absentees on the principal’s dashboard</li>
                <li>{!! $check !!} Weekly attendance chart per class</li>
            </ul>
        </div>
        <figure class="sx-media">
            <picture>
                <source type="image/webp" srcset="{{ asset('images/marketing/office.webp') }}">
                <img src="{{ asset('images/marketing/office.jpg') }}" width="2000" height="1125" alt="A Lagos secondary school principal at her desk with the day’s registers." loading="lazy">
            </picture>
        </figure>
    </div>
</section>

<section class="sx-split sx-split-tint" id="results">
    <div class="sx-wrap sx-split-inner is-flip">
        <div class="sx-split-copy">
            <p class="sx-eyebrow">Results</p>
            <h2 class="sx-h2">WAEC grades in one publish.</h2>
            <p class="sx-sub">CA, mid term and exam. A1 to F9. Class position. Then you publish the term.</p>
            <ul class="sx-list">
                <li>{!! $check !!} Teachers enter scores per subject</li>
                <li>{!! $check !!} Totals, grades and positions are worked out for you</li>
                <li>{!! $check !!} Families open the report the moment you publish</li>
            </ul>
        </div>
        <aside class="lp-card sx-report" aria-label="Sample First Term report card">
            <div class="lp-card-inner">
                <header>
                    <span>Ikeja Secondary School</span>
                    <span class="sx-tag">First Term 2025/2026</span>
                </header>
                <h3>Uche Abdullahi</h3>
                <p class="meta">JSS2A · Admission 212864</p>
                <table>
                    <thead>
                        <tr><th>Subject</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>English</td><td>18</td><td>42</td><td>60</td><td><b class="g-c">C4</b></td></tr>
                        <tr><td>Mathematics</td><td>16</td><td>38</td><td>54</td><td><b class="g-c">C6</b></td></tr>
                        <tr><td>Basic Science</td><td>22</td><td>45</td><td>67</td><td><b class="g-b">B3</b></td></tr>
                        <tr><td>Basic Technology</td><td>20</td><td>41</td><td>61</td><td><b class="g-c">C4</b></td></tr>
                        <tr><td>Social Studies</td><td>21</td><td>44</td><td>65</td><td><b class="g-b">B3</b></td></tr>
                        <tr><td>Civic Education</td><td>19</td><td>37</td><td>56</td><td><b class="g-c">C5</b></td></tr>
                        <tr><td>Business Studies</td><td>23</td><td>48</td><td>71</td><td><b class="g-b">B2</b></td></tr>
                        <tr><td>Agricultural Science</td><td>17</td><td>40</td><td>57</td><td><b class="g-c">C5</b></td></tr>
                        <tr><td>PHE</td><td>24</td><td>50</td><td>74</td><td><b class="g-b">B2</b></td></tr>
                    </tbody>
                </table>
                <div class="foot">
                    <span>Average 62.8 · Position 14 of 48</span>
                    <span>Attendance 92%</span>
                </div>
            </div>
        </aside>
    </div>
</section>

<section class="sx-split" id="parents">
    <div class="sx-wrap sx-split-inner">
        <div class="sx-split-copy">
            <p class="sx-eyebrow">Parents</p>
            <h2 class="sx-h2">Parents see only their child.</h2>
            <p class="sx-sub">A school code and the child’s name. They open the report. They do not see the rest of the hall.</p>
            <ul class="sx-list">
                <li>{!! $check !!} Join with a school code, no paperwork</li>
                <li>{!! $check !!} Results, attendance and circulars in one place</li>
                <li>{!! $check !!} Several children under one parent login</li>
            </ul>
        </div>
        <figure class="sx-media sx-media-frame">
            <picture>
                <source type="image/webp" srcset="{{ asset('images/marketing/parent-desk.webp') }}">
                <img src="{{ asset('images/marketing/parent-desk.jpg') }}" width="1440" height="540" alt="Parent view in Orangebook for Uche Abdullahi, JSS2A, showing average, position, attendance and grade." loading="lazy">
            </picture>
        </figure>
    </div>
</section>

{{-- ============ HOW IT WORKS ============ --}}
<section class="sx-section sx-section-tint">
    <div class="sx-wrap">
        <header class="sx-head">
            <p class="sx-eyebrow">How it works</p>
            <h2 class="sx-h2">From application to open desks in three steps.</h2>
        </header>
        <ol class="sx-steps">
            <li>
                <span class="sx-step-n">1</span>
                <h3>Tell us the hall</h3>
                <p>The organisation name, who to write to, and how many learners you keep.</p>
            </li>
            <li>
                <span class="sx-step-n">2</span>
                <h3>Read the figure</h3>
                <p>We write back with one bill for the term. Nothing is billed until you accept.</p>
            </li>
            <li>
                <span class="sx-step-n">3</span>
                <h3>Open the desks</h3>
                <p>Staff join on an invite. Families join with a school code. One login from there.</p>
            </li>
        </ol>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="sx-section" id="faq">
    <div class="sx-wrap sx-faq-grid">
        <header class="sx-head is-left">
            <p class="sx-eyebrow">FAQ</p>
            <h2 class="sx-h2">Before you apply.</h2>
            <p class="sx-sub">Anything else? Write to <a href="mailto:hello@orangebook.ng">hello@orangebook.ng</a>.</p>
        </header>
        <div class="lp-faq sx-faq">
            <details open>
                <summary>How much does it cost?</summary>
                <p>We quote after we know how many learners you keep. One organisation, one bill, per term. There is no public price list.</p>
            </details>
            <details>
                <summary>Who is it for?</summary>
                <p>Secondary and primary schools, NGO programmes, and training firms. If you take attendance and issue results, this is the book.</p>
            </details>
            <details>
                <summary>Do parents need a login?</summary>
                <p>Yes, for school halls. They join with a school code and the child’s name. They see only that child. NGO and training halls do not get a parents menu.</p>
            </details>
            <details>
                <summary>Does it do WAEC grades?</summary>
                <p>Yes. CA, mid term and exam. Grades A1 to F9. Class position. Then you publish the term.</p>
            </details>
            <details>
                <summary>Is there a trial?</summary>
                <p>There is no self serve trial. You apply. We quote. You accept, then the hall opens.</p>
            </details>
        </div>
    </div>
</section>

{{-- ============ APPLY ============ --}}
<section class="sx-apply" id="apply">
    <div class="sx-apply-bg" aria-hidden="true"></div>
    <div class="sx-wrap sx-apply-inner">
        <div class="sx-apply-copy">
            <p class="sx-eyebrow is-light">Get a quote</p>
            <h2 class="sx-h2">Tell us the size of the hall.</h2>
            <p>Every school, programme and training firm is priced on its own. Apply with the name of the organisation, who to write to, and how many learners you keep. We write back with a figure.</p>
            <ul class="sx-list is-light">
                <li>{!! $check !!} One bill per term</li>
                <li>{!! $check !!} Nothing is billed until you accept</li>
                <li>{!! $check !!} Staff and families onboard by invite and school code</li>
            </ul>
        </div>
        @include('marketing._apply_form', ['submit' => 'Apply for a quote'])
    </div>
</section>
@endsection

@push('head')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {"@@type": "Question", "name": "How much does it cost?", "acceptedAnswer": {"@@type": "Answer", "text": "We quote after we know how many learners you keep. One organisation, one bill, per term."}},
    {"@@type": "Question", "name": "Who is it for?", "acceptedAnswer": {"@@type": "Answer", "text": "Secondary and primary schools, NGO programmes, and training firms."}},
    {"@@type": "Question", "name": "Do parents need a login?", "acceptedAnswer": {"@@type": "Answer", "text": "Yes, for school halls. They join with a school code and the child’s name."}},
    {"@@type": "Question", "name": "Does it do WAEC grades?", "acceptedAnswer": {"@@type": "Answer", "text": "Yes. CA, mid term and exam. Grades A1 to F9."}}
  ]
}
</script>
@endpush
