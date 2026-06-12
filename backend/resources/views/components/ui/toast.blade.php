@props([
    'variant' => 'info',
    'title' => null,
])

@php($role = in_array($variant, ['danger', 'error'], strict: true) ? 'alert' : 'status')

<div
    role="{{ $role }}"
    aria-live="{{ $role === 'alert' ? 'assertive' : 'polite' }}"
    data-toast
    {{ $attributes->class(['toast', "toast-{$variant}"]) }}
>
    <div>
        @if ($title)
            <strong>{{ $title }}</strong>
        @endif
        <span>{{ $slot }}</span>
    </div>
    <x-ui.button variant="text" size="sm" data-toast-close aria-label="Fechar notificacao">
        Fechar
    </x-ui.button>
</div>
