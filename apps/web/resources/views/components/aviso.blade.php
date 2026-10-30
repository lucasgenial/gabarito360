@props(['tipo' => 'info'])
{{-- Aviso: <x-aviso tipo="sucesso|erro|atencao|info">texto</x-aviso> --}}
<div {{ $attributes->merge(['class' => 'g-aviso ' . $tipo]) }} role="{{ $tipo === 'erro' ? 'alert' : 'status' }}">{{ $slot }}</div>
