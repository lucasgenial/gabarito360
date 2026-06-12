@props([
    'label',
    'value',
    'context' => null,
    'status' => null,
    'variant' => 'info',
    'metric' => null,
])

<section {{ $attributes->class(['kpi-card', "kpi-card-{$variant}"]) }} aria-label="{{ $label }}">
    <span class="kpi-label">{{ $label }}</span>
    <strong class="kpi-value" @if ($metric) data-application-metric="{{ $metric }}" @endif>{{ $value }}</strong>
    @if ($context)
        <span class="kpi-context">{{ $context }}</span>
    @endif
    @if ($status)
        <span class="kpi-status">{{ $status }}</span>
    @endif
</section>
