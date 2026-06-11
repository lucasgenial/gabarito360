@props([
    'labelledby' => null,
    'variant' => 'default',
])

<section
    @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
    {{ $attributes->class(['card', "card-{$variant}" => $variant !== 'default']) }}
>
    {{ $slot }}
</section>
