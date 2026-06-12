@props([
    'label',
    'value',
    'context' => null,
    'status' => null,
    'variant' => 'info',
])

<section {{ $attributes->class(['kpi-card', "kpi-card-{$variant}"]) }} aria-label="{{ $label }}">
    <span class="kpi-label">{{ $label }}</span>
    <strong class="kpi-value">{{ $value }}</strong>
    @if ($context)
        <span class="kpi-context">{{ $context }}</span>
    @endif
    @if ($status)
        <span class="kpi-status">{{ $status }}</span>
    @endif
</section>
