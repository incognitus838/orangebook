@if(!empty($user) && Auth::user()?->isAdmin() && ($user->status ?? '') === 'active' && in_array((int) $user->usergroup_id, [5, 6, 7], true) && ((int) $user->usergroup_id !== 7 || optional(Auth::user()->school)->usesParents()))
<form method="POST" action="{{ url('/admin/people/'.$user->id.'/open') }}" class="ob-inline">
    @csrf
    <button class="crest-btn ob-btn-secondary" type="submit">Open desk</button>
</form>
@endif
