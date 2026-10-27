@props([
    'title' => '',
])
<div {{ $attributes->merge(['class' => 'ob-page']) }}>
    <div class="ob-page-head">
        <h1>{{ $title }}</h1>
        {{ $actions ?? '' }}
    </div>
    {{ $slot }}
</div>
