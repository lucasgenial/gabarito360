@props(['tipo' => 'info'])
@php($flash=is_string(session($tipo))&&trim(strip_tags((string)$slot))===trim(session($tipo))&& !($tipo==='erro'&&$errors->any()))
{{-- Aviso: <x-aviso tipo="sucesso|erro|atencao|info">texto</x-aviso> --}}
<div {{ $attributes->merge(['class' => 'g-aviso ' . $tipo]) }} @if($flash) data-g360-flash="{{ $tipo }}" @endif role="{{ $tipo === 'erro' ? 'alert' : 'status' }}">{{ $slot }}</div>
