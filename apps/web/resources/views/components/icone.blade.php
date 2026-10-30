@props(['nome'])
{{-- Ícone do sistema: <x-icone nome="casa" /> (lista em layouts/_icone) --}}
@include('layouts._icone', ['nome' => $nome])
