@props(['label' => 'Carregando'])

<div role="status" aria-live="polite" {{ $attributes->class('state-panel loading-state') }}>
    <span class="loading-indicator" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</div>
