@extends('layouts.app')
@section('tela','tela-catalogo-cadastro')
@include('catalogo-atendimento._assets')
@section('title','Consultar catálogo institucional')
@section('breadcrumb')<a href="{{ route('catalogo-atendimento.index') }}">Solicitações de instituições</a><span class="sep">/</span><span>Consultar catálogo</span>@endsection
@section('content')
<x-pagina titulo="Consultar catálogo institucional"><x-slot:sub>Localize a instituição e confira sua origem antes de revisar ou aprovar um cadastro.</x-slot:sub></x-pagina>
@include('catalogo-atendimento._erros')
@if($pedido)<section class="card catalogo-resumo"><h2>{{ $pedido['dados']['nome'] }}</h2><p>{{ $pedido['dados']['municipio_nome']??'Município informado no pedido' }} — {{ $pedido['dados']['uf'] }}</p><p><a href="{{ route('catalogo-atendimento.show',['id'=>$pedido['id']]) }}">Voltar ao atendimento</a></p></section>@endif
<form class="card catalogo-formulario" method="get" action="{{ route('catalogo-atendimento.buscar') }}">
@if($pedido)<input type="hidden" name="solicitacao" value="{{ $pedido['id'] }}">@endif
<p class="catalogo-ajuda">Informe um nome com pelo menos três caracteres, uma UF ou um código com sua fonte. O tipo ajuda a refinar a busca.</p>
<div class="catalogo-campos">
<div class="catalogo-campo catalogo-campo-inteiro"><label for="busca-nome">Nome ou sigla</label><input id="busca-nome" aria-invalid="{{ $errors->has('busca')?'true':'false' }}" aria-describedby="erro-busca" name="busca" value="{{ $f['busca']??'' }}" maxlength="100">@include('catalogo-atendimento._erro-campo',['campo'=>'busca','erroId'=>'erro-busca'])</div>
<div class="catalogo-campo"><label for="busca-uf">UF</label><select id="busca-uf" name="uf"><option value="">Todas</option>@foreach($opcoes['ufs'] as $uf)<option value="{{ $uf['sigla'] }}" @selected(($f['uf']??'')===$uf['sigla'])>{{ $uf['nome'] }}</option>@endforeach</select></div>
<div class="catalogo-campo"><label for="busca-tipo">Tipo de instituição</label><select id="busca-tipo" name="tipo"><option value="">Todos</option>@foreach(\App\Http\Controllers\InstituicoesPessoaisController::TIPOS as $valor=>$rotulo)<option value="{{ $valor }}" @selected(($f['tipo']??'')===$valor)>{{ $rotulo }}</option>@endforeach</select></div>
</div>
<details class="catalogo-secao catalogo-adicionais" @if(!empty($f['codigo'])) open @endif><summary>Buscar por código</summary><div class="catalogo-campos"><div class="catalogo-campo"><label for="busca-fonte">Fonte do código</label><select id="busca-fonte" name="namespace"><option value="">Selecione</option>@foreach(['inep_escola'=>'INEP da escola','inep_ies'=>'INEP da IES','plataforma'=>'Código da plataforma'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(($f['namespace']??'')===$valor)>{{ $rotulo }}</option>@endforeach</select></div><div class="catalogo-campo"><label for="busca-codigo">Código exato</label><input id="busca-codigo" aria-invalid="{{ $errors->has('codigo')?'true':'false' }}" aria-describedby="erro-busca-codigo" name="codigo" maxlength="30" value="{{ $f['codigo']??'' }}">@include('catalogo-atendimento._erro-campo',['campo'=>'codigo','erroId'=>'erro-busca-codigo'])</div></div></details>
<div class="catalogo-acoes"><a class="btn btn-ghost" href="{{ route('catalogo-atendimento.buscar',$pedido?['solicitacao'=>$pedido['id']]:[]) }}">Limpar filtros</a><button class="btn btn-primary">Buscar instituições</button></div>
</form>
@if($dados)@forelse($dados['itens'] as $e)
<section class="card catalogo-resumo">@include('instituicoes._identidade',['tipos'=>\App\Http\Controllers\InstituicoesPessoaisController::TIPOS])
@if(!$pedido)<div class="catalogo-acoes"><a class="btn btn-secondary" href="{{ route('catalogo-atendimento.editar',['unidade'=>$e['unidade_id']]) }}">Revisar cadastro</a><a class="btn btn-ghost" href="{{ route('catalogo-atendimento.autoridades',['unidade'=>$e['unidade_id']]) }}">Gerir permissão de manutenção</a></div>@endif
@if($pedido && $pedido['situacao']==='em_analise' && $e['elegivel'])
@php $selecionada=old('unidade_id')===$e['unidade_id'];$campoId='correspondencia-'.$e['unidade_id']; @endphp
<details class="catalogo-secao catalogo-adicionais" @if($selecionada) open @endif><summary>Conferir correspondência com o pedido</summary><form method="post" action="{{ route('catalogo-atendimento.acao',['id'=>$pedido['id']]) }}">@csrf <x-revisao-acesso /><input type="hidden" name="versao" value="{{ $pedido['versao'] }}"><input type="hidden" name="acao" value="aprovar_existente"><input type="hidden" name="unidade_id" value="{{ $e['unidade_id'] }}"><input type="hidden" name="edicao_id" value="{{ $e['edicao_id'] }}">
<label for="{{ $campoId }}">Por que esta é a instituição solicitada? *</label><textarea id="{{ $campoId }}" name="motivo" required minlength="5" maxlength="1000" rows="3" aria-describedby="{{ $campoId }}-erro" aria-invalid="{{ $selecionada && $errors->has('motivo')?'true':'false' }}">{{ $selecionada?old('motivo'):'' }}</textarea>
@if($selecionada)@include('catalogo-atendimento._erro-campo',['campo'=>'motivo','erroId'=>$campoId.'-erro'])@else<p id="{{ $campoId }}-erro" hidden></p>@endif
<label class="catalogo-confirmacao"><input type="checkbox" name="conferencia" required value="1" aria-invalid="{{ $selecionada && $errors->has('conferencia')?'true':'false' }}" aria-describedby="{{ $campoId }}-confirmacao"><span>Conferi que esta é a instituição solicitada.</span></label>@if($selecionada)@include('catalogo-atendimento._erro-campo',['campo'=>'conferencia','erroId'=>$campoId.'-confirmacao'])@else<p id="{{ $campoId }}-confirmacao" hidden></p>@endif<button class="btn btn-primary">Aprovar com esta instituição</button></form></details>@endif
</section>
@empty<section class="card catalogo-resumo"><h2>Nenhuma instituição encontrada</h2><p>Confira o nome, os filtros e o código antes de cadastrar uma nova referência.</p><p><a href="{{ $pedido?route('catalogo-atendimento.show',['id'=>$pedido['id']]):route('catalogo-atendimento.novo') }}">{{ $pedido?'Voltar ao atendimento':'Cadastrar referência da plataforma' }}</a></p></section>@endforelse
@include('instituicoes._paginas',['p'=>$dados['paginacao'],'rota'=>'catalogo-atendimento.buscar','f'=>request()->except('page')])
@endif
@endsection
