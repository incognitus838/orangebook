<input type="search" class="ob-pick-search" placeholder="Search names" oninput="var q=this.value.toLowerCase(); this.nextElementSibling.querySelectorAll('.ob-check').forEach(function(el){ el.style.display = el.innerText.toLowerCase().indexOf(q)===-1 ? 'none':'flex'; });">
<div class="ob-pick">
    @forelse($people as $person)
        <label class="ob-check">
            <input type="checkbox" name="{{ $name }}[]" value="{{ $person->id }}">
            <span>{{ $person->name }}@if(!empty($person->email) && !empty($withEmail)) · {{ $person->email }}@endif</span>
        </label>
    @empty
        <p class="ob-help" style="margin:8px 0">No people yet.</p>
    @endforelse
</div>
