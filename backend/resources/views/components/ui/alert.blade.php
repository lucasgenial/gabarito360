@props([
    'variant' => 'info',
    'title' => null,
])

@php($role = in_array($variant, ['danger', 'error'], strict: true) ? 'alert' : 'status')

<div role="{{ $role }}" {{ $attributes->class(['alert', "alert-{$variant}"]) }}>
    @if ($title)
        <strong>{{ $title }}</strong>
    @endif
    <span>{{ $slot }}</span>
</div>
