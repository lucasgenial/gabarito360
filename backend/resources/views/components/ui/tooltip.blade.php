@props([
    'id',
    'label',
    'content',
])

<span {{ $attributes->class('tooltip') }}>
    <button type="button" class="tooltip-trigger" aria-describedby="{{ $id }}">
        {{ $label }}
    </button>
    <span id="{{ $id }}" class="tooltip-content" role="tooltip">{{ $content }}</span>
</span>
