@props(['caption' => null])

<div class="table-wrap">
    <table {{ $attributes }}>
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        {{ $slot }}
    </table>
</div>
