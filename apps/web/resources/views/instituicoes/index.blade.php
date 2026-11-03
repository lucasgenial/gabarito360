@extends('layouts.app')
@section('title','Minhas instituições')
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Minhas instituições</span>@endsection
@section('content')
<x-pagina titulo="Minhas instituições"><x-slot:sub>Referências de atuação no seu espaço pessoal.</x-slot:sub><a class="btn btn-primary" href="{{ route('instituicoes.buscar') }}">{{ \App\Support\Acesso::pode('catalogo.referencia_pessoal.gerir')?'Adicionar instituição':'Consultar catálogo' }}</a></x-pagina>
<p style="margin:20px 0;">Use esta lista para registrar as instituições em que você atua. Adicionar uma instituição não confirma vínculo profissional e não libera acesso ao ambiente ou aos dados da escola.</p>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
<div style="display:grid;gap:16px;margin-top:20px;">
@forelse($dados['itens'] as $r)
  <article class="card card-pad">
    @include('instituicoes._identidade',['e'=>$r['instituicao']])
    <p><span class="badge {{ $r['situacao']==='ativa' ? 'badge-success' : 'badge-info' }}">{{ $r['situacao']==='ativa' ? 'Referência pessoal ativa' : 'Arquivada' }}</span></p>
    @unless($r['edicao_corrente'])<p>A edição do catálogo mudou. Esta declaração mantém os dados que você confirmou na época.</p>@endunless
    @unless($r['elegivel_atualmente'])<p>Referência histórica: consulte a situação atual antes de um novo uso.</p>@endunless
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;">
      @if($r['situacao']==='ativa' && \App\Support\Acesso::pode('catalogo.referencia_pessoal.gerir'))
        <form method="POST" action="{{ route('instituicoes.arquivar',$r['id']) }}">@csrf<input type="hidden" name="versao" value="{{ $r['versao'] }}"><button class="btn btn-secondary" type="submit">Arquivar referência</button></form>
      @endif
      @if($r['situacao']==='arquivada' || !$r['edicao_corrente'])<a class="btn btn-ghost" href="{{ route('instituicoes.buscar',['namespace'=>$r['instituicao']['namespace'],'codigo'=>$r['instituicao']['codigo'],'situacao'=>'todas']) }}">Conferir no catálogo</a>@endif
    </div>
  </article>
@empty
  <div class="card card-pad"><h2>Nenhuma instituição adicionada</h2><p>Busque uma instituição no catálogo nacional e confira os dados antes de adicioná-la. Seu espaço pessoal continuará separado do ambiente institucional.</p></div>
@endforelse
</div>
@include('instituicoes._paginas',['rota'=>'instituicoes.index'])
@endsection
