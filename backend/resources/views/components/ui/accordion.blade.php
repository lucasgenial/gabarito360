@props([
    'title' => null,
    'open' => false,
])

<details {{ $attributes->class('accordion card') }} @if ($open) open @endif>
    <summary class="accordion-summary">
        @if (isset($summary))
            {{ $summary }}
        @else
            <span>{{ $title }}</span>
        @endif
        <span aria-hidden="true" class="accordion-indicator">+</span>
    </summary>
    <div class="accordion-content">
        {{ $slot }}
    </div>
</details>
