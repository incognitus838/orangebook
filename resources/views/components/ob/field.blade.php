@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => '',
    'error' => null,
])
@php
    $id = $attributes->get('id', $name);
@endphp
<div class="ob-field">
    @if($type === 'checkbox' || $type === 'radio')
        <label class="ob-field-check-label" @if($id) for="{{ $id }}" @endif>
            <input
                @if($id) id="{{ $id }}" @endif
                type="{{ $type }}"
                name="{{ $name }}"
                value="{{ $value }}"
                class="ob-field-check"
                {{ $attributes->except('id') }}
            >
            {{ $label }}
        </label>
    @else
        @if($label !== '')
            <label class="ob-field-label" @if($id) for="{{ $id }}" @endif>{{ $label }}</label>
        @endif
        @if($type === 'textarea')
            <textarea
                @if($id) id="{{ $id }}" @endif
                name="{{ $name }}"
                class="ob-input{{ $error ? ' is-error' : '' }}"
                {{ $attributes->except('id') }}
            >{{ $value }}{{ $slot }}</textarea>
        @elseif($type === 'select')
            <select
                @if($id) id="{{ $id }}" @endif
                name="{{ $name }}"
                class="ob-input{{ $error ? ' is-error' : '' }}"
                {{ $attributes->except('id') }}
            >{{ $slot }}</select>
        @else
            <input
                @if($id) id="{{ $id }}" @endif
                type="{{ $type }}"
                name="{{ $name }}"
                value="{{ $value }}"
                class="ob-input{{ $error ? ' is-error' : '' }}"
                {{ $attributes->except('id') }}
            >
        @endif
    @endif
    @if($error)
        <p class="ob-field-error">{{ $error }}</p>
    @endif
</div>
