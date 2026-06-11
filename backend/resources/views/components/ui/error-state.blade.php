@props([
    'title' => 'Nao foi possivel carregar o conteudo',
    'action' => null,
])

<section role="alert" {{ $attributes->class('state-panel error-state') }}>
    <strong>{{ $title }}</strong>
    <span>{{ $slot }}</span>
    @if ($action)
        <div class="state-actions">{{ $action }}</div>
    @endif
</section>
