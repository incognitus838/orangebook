@extends('layouts.marketing')

@section('title', 'Page not found. Orangebook')
@section('meta', 'That page is not in Orangebook.')

@section('content')
<article class="lp-legal">
    <h1>This page is not here.</h1>
    <p>The address does not match a page in Orangebook. Open the homepage, or apply for a quote.</p>
    <p><a class="lp-btn" href="{{ url('/') }}">Orangebook home</a></p>
</article>
@endsection
