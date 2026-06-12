@props([
    'name',
    'src' => null,
    'size' => 'md',
])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span
    role="img"
    aria-label="{{ $name }}"
    {{ $attributes->class(['avatar', "avatar-{$size}"]) }}
>
    @if ($src)
        <img src="{{ $src }}" alt="">
    @else
        <span aria-hidden="true">{{ $initials }}</span>
    @endif
</span>
