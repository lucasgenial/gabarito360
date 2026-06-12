@props([
    'title',
    'series' => [],
    'description' => null,
    'valueLabel' => 'Valor',
])

@php($maximum = max(1, ...collect($series)->pluck('value')->map(fn ($value) => (float) $value)->all()))

<section {{ $attributes->class('chart-card') }} aria-labelledby="{{ $attributes->get('id', 'chart') }}-title">
    <header class="chart-heading">
        <h2 id="{{ $attributes->get('id', 'chart') }}-title">{{ $title }}</h2>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </header>

    <ul class="chart-bars" aria-hidden="true">
        @foreach ($series as $item)
            <li>
                <span>{{ $item['label'] }}</span>
                <progress max="{{ $maximum }}" value="{{ $item['value'] }}"></progress>
                <strong>{{ $item['display'] ?? $item['value'] }}</strong>
            </li>
        @endforeach
    </ul>

    <details class="chart-data">
        <summary>Consultar dados do grafico</summary>
        <x-ui.table :caption="$title">
            <thead>
                <tr>
                    <th scope="col">Categoria</th>
                    <th scope="col">{{ $valueLabel }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($series as $item)
                    <tr>
                        <th scope="row">{{ $item['label'] }}</th>
                        <td>{{ $item['display'] ?? $item['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </details>
</section>
