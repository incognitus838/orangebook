@extends('layouts.teacher.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Edit profile</h1>
    </div>
    @include('partials.message')
    @include('crest.account._profile_form', ['action' => url('/teacher/editprofile'), 'user' => Auth::user()])
</div>
@endsection
