@extends('layouts.app')
@section('tela','tela-catalogo-cadastro')
@include('catalogo-atendimento._assets')
@php $prefix=$modo==='atendimento'?'catalogo-atendimento':($modo==='gestao'?'catalogo-gestao':'catalogo-pedidos');$titulo=$modo==='atendimento'?'Solicitações de instituições':($modo==='gestao'?'Cadastro institucional':'Minhas solicitações'); @endphp
@section('title',$titulo)
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>{{ $titulo }}</span>@endsection
@section('content')
<x-pagina :titulo="$titulo"><x-slot:sub>{{ $modo==='atendimento'?'Confira os pedidos recebidos e acompanhe as respostas da equipe.':($modo==='gestao'?'Revise as unidades liberadas para manutenção pela sua instituição.':'Acompanhe os pedidos enviados ao suporte. As instituições não recebem estes protocolos.') }}</x-slot:sub>
@if($modo==='minhas-solicitacoes')<a class="btn btn-primary" href="{{ route('catalogo-pedidos.novo') }}">Solicitar cadastro</a>@elseif($modo==='atendimento')<a class="btn btn-primary" href="{{ route('catalogo-atendimento.buscar') }}">Consultar e manter catálogo</a>@endif</x-pagina>
@include('catalogo-atendimento._erros')
@if($modo==='atendimento')<p style="margin-top:16px;"><a href="{{ route('catalogo-atendimento.novo') }}">Cadastrar referência da plataforma</a> <span class="page-sub">— use após conferir que a instituição ainda não está no catálogo.</span></p>@elseif($modo==='minhas-solicitacoes')<p style="margin-top:16px;"><a href="{{ route('instituicoes.index') }}">Voltar às minhas instituições</a></p>@endif
@if($modo!=='gestao')<form class="catalogo-filtro" method="get" action="{{ route($prefix.'.index') }}" style="max-width:380px;margin-top:24px;"><label for="sol-filtro">Filtrar por situação</label><div class="catalogo-busca-linha"><select id="sol-filtro" name="situacao"><option value="">Todas</option>@foreach(['enviada'=>'Enviada','em_analise'=>'Em análise','aguardando_complementacao'=>'Aguardando complementação','aprovada'=>'Aprovada','indeferida'=>'Indeferida','cancelada'=>'Cancelada'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(request('situacao')===$valor)>{{ $rotulo }}</option>@endforeach</select><button class="btn btn-secondary">Filtrar</button></div></form>@endif
@if(count($dados['itens']))
  @if($modo==='gestao')<div style="display:grid;gap:16px;margin-top:24px;">@foreach($dados['itens'] as $e)<article class="card card-pad">@include('instituicoes._identidade',['tipos'=>\App\Http\Controllers\InstituicoesPessoaisController::TIPOS])<p style="margin-top:16px;"><a class="btn btn-secondary" href="{{ route('catalogo-gestao.editar',['unidade'=>$e['unidade_id']]) }}">Revisar cadastro</a></p></article>@endforeach</div>
  @else
  <div class="card catalogo-lista" style="margin-top:24px;overflow-x:auto;"><table class="lista-cel"><thead><tr><th>Instituição solicitada</th><th>Situação</th><th>Atualização</th><th>Acompanhamento</th></tr></thead><tbody>@foreach($dados['itens'] as $item)<tr>
  <td data-label="Instituição"><strong>{{ $item['nome_instituicao']??($item['dados']['nome']??'Instituição solicitada') }}</strong>@if(!empty($item['municipio_nome']))<span class="page-sub">{{ $item['municipio_nome'] }} — {{ $item['uf'] }}</span>@endif</td>
  <td data-label="Situação">@include('catalogo-atendimento._estado',['situacao'=>$item['situacao']])</td>
  <td data-label="Atualização">{{ \Illuminate\Support\Carbon::parse($item['atualizado_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
  <td data-label="Acompanhamento"><a class="btn btn-secondary btn-sm" href="{{ route($prefix.'.show',['id'=>$item['id']]) }}">{{ $modo==='atendimento'?'Abrir atendimento':'Acompanhar solicitação' }}</a></td>
  </tr>@endforeach</tbody></table></div>
  @endif
@else
<section class="card card-pad" style="margin-top:24px;max-width:1120px;">
<h2 style="font-size:20px;margin-bottom:8px;">{{ $modo==='gestao'?'Manutenção ainda não liberada':(request('situacao')?'Nenhuma solicitação neste filtro':'Nenhuma solicitação enviada') }}</h2>
<p class="page-sub">{{ $modo==='gestao'?'Fale com o suporte para conferir o cadastro e a instituição responsável. Após a liberação, suas unidades aparecerão aqui para manutenção.':(request('situacao')?'Escolha outra situação ou consulte todas as solicitações.':'Os pedidos aparecerão aqui com o nome da instituição, a situação e as respostas da equipe.') }}</p>
@if($modo==='gestao')<p style="margin-top:16px;"><a href="{{ route('leads.index') }}">Falar com o suporte</a></p>@endif
</section>
@endif
@if(isset($dados['paginacao']))@include('instituicoes._paginas',['rota'=>$prefix.'.index','f'=>request()->only('situacao')])@endif
@endsection