@props([
    'items' => [],
    'label' => 'Navegacao estrutural',
])

<nav aria-label="{{ $label }}" {{ $attributes->class('breadcrumb') }}>
    <ol>
        @foreach ($items as $item)
            <li>
                @if (! $loop->last && isset($item['href']))
                    <a href="{{ $item['href'] }}" @if ($item['navigate'] ?? false) wire:navigate @endif>
                        {{ $item['label'] }}
                    </a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
