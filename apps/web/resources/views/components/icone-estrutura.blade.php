@props(['nome'])
@php
$catalogo=json_decode(file_get_contents(resource_path('brand/estrutura-icones.json')),true,512,JSON_THROW_ON_ERROR);
$icone=$catalogo['icones'][$nome]??$catalogo['icones']['mais'];
@endphp
<svg class="icone-estrutura" viewBox="{{ $icone['viewBox'] }}" width="20" height="20" fill="currentColor" aria-hidden="true">@foreach($icone['paths'] as $path)<path d="{{ $path }}"/>@endforeach</svg>
