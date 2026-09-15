<div class="crest-panel">
    <h2 class="crest-panel-title">Birthdays today</h2>
    <p class="crest-kicker">Students</p>
    @forelse($students as $profile)
        <p class="crest-person">
            {{ $profile->firstname }} {{ $profile->lastname }}
            @if($profile->date_of_birth)
                <span>{{ \Carbon\Carbon::parse($profile->date_of_birth)->age }}</span>
            @endif
        </p>
    @empty
        <p class="crest-empty">None today.</p>
    @endforelse
    <p class="crest-kicker" style="margin-top:18px;">Teachers</p>
    @forelse($teachers as $profile)
        <p class="crest-person">
            {{ $profile->firstname }} {{ $profile->lastname }}
            @if($profile->date_of_birth)
                <span>{{ \Carbon\Carbon::parse($profile->date_of_birth)->age }}</span>
            @endif
        </p>
    @empty
        <p class="crest-empty">None today.</p>
    @endforelse
</div>
