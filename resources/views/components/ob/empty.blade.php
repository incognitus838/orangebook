@props([
    'action' => null,
    'actionLabel' => null,
])
<div {{ $attributes->merge(['class' => 'ob-empty']) }}>
    <p>{{ $slot }}</p>
    @if($action && $actionLabel)
        <a class="ob-btn ob-btn-quiet" href="{{ $action }}">{{ $actionLabel }}</a>
    @endif
</div>
