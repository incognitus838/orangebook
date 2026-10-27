@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'submit',
])
@php
    $class = 'ob-btn';
    if ($variant === 'secondary') {
        $class .= ' ob-btn-secondary';
    } elseif ($variant === 'quiet') {
        $class .= ' ob-btn-quiet';
    } elseif ($variant === 'danger') {
        $class .= ' ob-btn-danger';
    }
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
