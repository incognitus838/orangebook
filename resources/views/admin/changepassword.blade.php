@extends('layouts.admin.layout')
@section('content')
<div class="ob-page">
    <div class="ob-head">
        <h1 class="admin-h1">Change password</h1>
    </div>
    @include('partials.message')
    @include('crest.account._password_form', ['action' => url('/admin/changepassword')])
</div>
@endsection
