@props([
    'id',
    'title',
    'description' => null,
    'side' => 'start',
])

<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    data-drawer
    {{ $attributes->class(['drawer', "drawer-{$side}"]) }}
>
    <div class="drawer-content">
        <header class="drawer-heading">
            <div>
                <h2 id="{{ $id }}-title">{{ $title }}</h2>
                @if ($description)
                    <p id="{{ $id }}-description">{{ $description }}</p>
                @endif
            </div>
            <x-ui.button variant="neutral" size="sm" data-drawer-close aria-label="Fechar menu">
                Fechar
            </x-ui.button>
        </header>
        {{ $slot }}
    </div>
</dialog>
