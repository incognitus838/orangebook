@php
    $obProfile = optional(Auth::user()->userprofile);
    $obName = trim((string) (Auth::user()->name ?: ''));
    if ($obProfile && ($obProfile->firstname || $obProfile->lastname)) {
        $obName = trim($obProfile->firstname.' '.$obProfile->lastname) ?: $obName;
    }
    $obParts = preg_split('/\s+/', $obName !== '' ? $obName : 'A');
    $obInitials = count($obParts) > 1
        ? strtoupper(mb_substr($obParts[0], 0, 1).mb_substr($obParts[1], 0, 1))
        : strtoupper(mb_substr($obParts[0], 0, min(2, mb_strlen($obParts[0]))));
    $obAvatar = (string) ($obProfile->avatar ?? '');
    $obGeneric = (bool) preg_match('/default-user|female\.png|male\.png|demologo/i', $obAvatar);
    $obHasPhoto = $obAvatar !== '' && !$obGeneric;
    $obDim = ($size ?? 'sm') === 'md' ? 'w-10 h-10' : 'w-8 h-8';
@endphp
@if($obHasPhoto)
    <img src="{{ asset(ltrim($obAvatar, '/')) }}" alt="" class="{{ $obDim }} rounded-full crest-face-img">
@else
    <span class="crest-face-initials {{ $obDim }}" aria-hidden="true">{{ $obInitials }}</span>
@endif
