@props([
    'id',
    'panel',
    'selected' => false,
])

<button
    id="{{ $id }}"
    type="button"
    role="tab"
    aria-controls="{{ $panel }}"
    aria-selected="{{ $selected ? 'true' : 'false' }}"
    tabindex="{{ $selected ? '0' : '-1' }}"
    {{ $attributes->class('tab') }}
>
    {{ $slot }}
</button>
