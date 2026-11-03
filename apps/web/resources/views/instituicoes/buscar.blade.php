@extends('layouts.app')
@section('title','Buscar instituição')
@section('breadcrumb')<a href="{{ route('instituicoes.index') }}">Minhas instituições</a><span class="sep">/</span><span>Buscar instituição</span>@endsection
@section('content')
<x-pagina titulo="Buscar instituição"><x-slot:sub>Encontre a referência oficial por localização, nome, sigla ou código.</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
<form method="GET" action="{{ route('instituicoes.buscar') }}" class="card card-pad" style="margin-top:20px;" id="catalogo-busca" data-municipios="{{ route('instituicoes.municipios',[],false) }}">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:18px;">
    <div><label for="cat-tipo">Tipo de instituição</label><select class="select" id="cat-tipo" name="tipo"><option value="">Todos os tipos</option>@foreach($tipos as $id=>$nome)<option value="{{ $id }}" @selected(($f['tipo']??'')===$id)>{{ $nome }}</option>@endforeach</select></div>
    <div><label for="cat-uf">UF</label><select class="select" id="cat-uf" name="uf"><option value="">Todas as UFs</option>@foreach($ufs as $uf)<option value="{{ $uf['sigla'] }}" @selected(($f['uf']??'')===$uf['sigla'])>{{ $uf['nome'] }} ({{ $uf['sigla'] }})</option>@endforeach</select></div>
    <div><label for="cat-municipio">Município</label><select class="select" id="cat-municipio" name="municipio_ibge" @disabled(empty($f['uf']))><option value="">Todos os municípios da UF</option>@foreach($municipios as $m)<option value="{{ $m['codigo_ibge'] }}" @selected(($f['municipio_ibge']??'')===$m['codigo_ibge'])>{{ $m['nome'] }}</option>@endforeach</select></div>
    <div><label for="cat-municipio-busca">Localizar município na UF</label><div style="display:flex;gap:8px;"><input class="input" id="cat-municipio-busca" type="search" maxlength="100" placeholder="Nome do município"><button class="btn btn-secondary" id="cat-carregar" type="button">Localizar</button></div><span id="cat-status" role="status" aria-live="polite"></span><div><button class="btn btn-ghost btn-sm" id="cat-anterior" type="button" hidden>Municípios anteriores</button><button class="btn btn-ghost btn-sm" id="cat-proximo" type="button" hidden>Mais municípios</button></div></div>
    <div><label for="cat-busca">Nome ou sigla</label><input class="input" id="cat-busca" name="busca" maxlength="100" value="{{ $f['busca']??'' }}" placeholder="Ex.: nome da escola ou sigla da IES"></div>
    <div><label for="cat-dependencia">Dependência administrativa</label><select class="select" id="cat-dependencia" name="dependencia"><option value="">Todas</option>@foreach(['federal'=>'Federal','estadual'=>'Estadual','municipal'=>'Municipal','privada'=>'Privada','especial'=>'Especial'] as $k=>$n)<option value="{{ $k }}" @selected(($f['dependencia']??'')===$k)>{{ $n }}</option>@endforeach</select></div>
    <div><label for="cat-fonte">Fonte do código</label><select class="select" id="cat-fonte" name="namespace"><option value="">Selecione ao buscar por código</option><option value="inep_escola" @selected(($f['namespace']??'')==='inep_escola')>INEP da escola</option><option value="inep_ies" @selected(($f['namespace']??'')==='inep_ies')>INEP da IES</option><option value="plataforma" @selected(($f['namespace']??'')==='plataforma')>Código da plataforma</option></select></div>
    <div><label for="cat-codigo">Código exato</label><input class="input" id="cat-codigo" name="codigo" maxlength="30" value="{{ $f['codigo']??'' }}"><small>O código dispensa UF e município. Limpe outros filtros para uma busca nacional direta.</small></div>
    <div><label for="cat-situacao">Situação na fonte</label><select class="select" id="cat-situacao" name="situacao"><option value="">Correntes, incluindo IES sem situação informada</option><option value="todas" @selected(($f['situacao']??'')==='todas')>Todas, incluindo referências históricas</option></select></div>
  </div>
  <p class="page-sub" style="margin-top:16px;">Informe UF, município, código oficial ou pelo menos três caracteres do nome. O catálogo reúne escolas e sedes administrativas de IES; não representa todos os campi e polos.</p>
  <button class="btn btn-primary" type="submit">Buscar instituições</button><a class="btn btn-ghost" href="{{ route('instituicoes.buscar') }}">Limpar filtros</a>
</form>
<p id="cat-resultados-status" role="status" aria-live="polite"></p>
@if($dados!==null)<section id="cat-resultados">
  <h2 style="margin-top:24px;">{{ $dados['paginacao']['total'] }} instituição(ões) encontrada(s)</h2>
  <div style="display:grid;gap:16px;margin-top:16px;">
  @forelse($dados['itens'] as $e)<article class="card card-pad">@include('instituicoes._identidade')<a class="btn btn-secondary" href="{{ route('instituicoes.conferir',['unidade'=>$e['unidade_id'],'edicao_id'=>$e['edicao_id']]) }}">Conferir instituição</a></article>
  @empty<div class="card card-pad"><h3>Nenhuma instituição encontrada com estes filtros</h3><p>Confira a UF e o município ou tente o nome, a sigla ou o código oficial.</p>@if(\App\Support\Acesso::pode('catalogo.solicitar'))<a class="btn btn-secondary" href="{{ route('catalogo-pedidos.novo') }}">Solicitar cadastro ao suporte</a>@else<p>Para instituições ausentes, consulte o suporte.</p>@endif</div>@endforelse
  </div>
  @include('instituicoes._paginas',['rota'=>'instituicoes.buscar'])
</section>@endif
@endsection
@push('scripts')<script src="{{ asset('js/catalogo-instituicoes.js') }}?v={{ @filemtime(public_path('js/catalogo-instituicoes.js')) }}"></script>@endpush
