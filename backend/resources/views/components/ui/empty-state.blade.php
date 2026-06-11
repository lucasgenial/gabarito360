@props([
    'title' => 'Nenhum resultado encontrado',
    'action' => null,
    'compact' => false,
])

<div {{ $attributes->class(['state-panel', 'empty-state', 'state-panel-compact' => $compact]) }}>
    <strong>{{ $title }}</strong>
    @if (trim((string) $slot))
        <span>{{ $slot }}</span>
    @endif
    @if ($action)
        <div class="state-actions">{{ $action }}</div>
    @endif
</div>
