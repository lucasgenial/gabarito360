@props([
    'id',
    'tab',
    'active' => false,
])

<section
    id="{{ $id }}"
    role="tabpanel"
    aria-labelledby="{{ $tab }}"
    @if (! $active) hidden @endif
    {{ $attributes->class('tab-panel') }}
>
    {{ $slot }}
</section>
