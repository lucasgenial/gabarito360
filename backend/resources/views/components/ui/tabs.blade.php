@props([
    'label',
])

<div data-tabs {{ $attributes->class('tabs') }}>
    <div role="tablist" aria-label="{{ $label }}" class="tab-list">
        {{ $tabs }}
    </div>
    <div class="tab-panels">
        {{ $slot }}
    </div>
</div>
