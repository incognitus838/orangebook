@extends('layouts.admin.layout')
@section('content')
<div>
    <h1 class="admin-h1 my-3">School branding</h1>
    @include('partials.message')
    <form method="POST" action="{{ url('/admin/crest/branding') }}" class="bg-white border p-4 max-w-lg">
        @csrf
        <label class="block text-sm mt-2">School name</label>
        <input class="border px-2 py-1 w-full" name="name" value="{{ $school->name }}" required>
        <label class="block text-sm mt-2">Primary colour</label>
        <input class="border px-2 py-1 w-full" type="color" name="primary_color" value="{{ $school->primary_color ?: '#1B2A4A' }}">
        <label class="block text-sm mt-2">Custom domain (Pro)</label>
        <input class="border px-2 py-1 w-full" name="custom_domain" value="{{ $school->custom_domain }}" placeholder="results.yourschool.ng">
        <p class="text-xs text-gray-500 mt-2">Subdomain: {{ $school->slug }}.{{ config('app.platform_domain') }}</p>
        <p class="text-sm mt-4">Parent join code: <strong>{{ $school->join_code ?: '—' }}</strong></p>
        <p class="text-xs" style="color:var(--crest-muted)">Parents use this on {{ url('/join') }} with the child’s admission number and date of birth.</p>
        <button class="mt-4 bg-gray-900 text-white text-sm px-4 py-2">Save</button>
    </form>
</div>
@endsection
