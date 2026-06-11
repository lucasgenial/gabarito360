@props([
    'id',
    'title',
    'description' => null,
])

<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    data-modal
    {{ $attributes->class('modal') }}
>
    <div class="modal-content">
        <header class="modal-heading">
            <div>
                <h2 id="{{ $id }}-title">{{ $title }}</h2>
                @if ($description)
                    <p id="{{ $id }}-description">{{ $description }}</p>
                @endif
            </div>
            <x-ui.button variant="neutral" size="sm" data-modal-close aria-label="Fechar modal">
                Fechar
            </x-ui.button>
        </header>
        {{ $slot }}
    </div>
</dialog>
