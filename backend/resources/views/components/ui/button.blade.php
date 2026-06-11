@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    $classes = collect([
        'button',
        "button-{$variant}",
        $size !== 'md' ? "button-{$size}" : null,
    ])->filter()->join(' ');
@endphp

@if ($href)
    <a
        href="{{ $disabled ? null : $href }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->class($classes) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled || $loading)
        @if ($loading) aria-busy="true" @endif
        {{ $attributes->class($classes) }}
    >
        @if ($loading)
            <span class="loading-indicator loading-indicator-inline" aria-hidden="true"></span>
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
