@php($trilha=\App\Support\Breadcrumbs::itens(request(),is_array($dados??null)?$dados:[],is_array($prova??null)?$prova:[]))
@if($trilha)
<nav class="breadcrumb breadcrumb-dinamico" aria-label="Caminho da página"><ol>
@foreach($trilha as $item)<li>@if($item['url'])<a href="{{ $item['url'] }}">{{ $item['rotulo'] }}</a>@else<span aria-current="page">{{ $item['rotulo'] }}</span>@endif</li>@endforeach
</ol></nav>
@elseif(View::hasSection('breadcrumb'))<nav class="breadcrumb" aria-label="Caminho da página">@yield('breadcrumb')</nav>@endif
