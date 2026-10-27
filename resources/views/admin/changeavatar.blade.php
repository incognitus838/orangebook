@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Change photo</h1>
    </div>
    @include('partials.message')
    @include('crest.account._avatar_form', [
        'action' => url('/admin/changeavatar'),
        'avatarUrl' => optional(Auth::user()->userprofile)->avatar
            ? Auth::user()->userprofile->AvatarPath
            : asset('uploads/user/avatar/default-user.jpg'),
    ])
</div>
@endsection
