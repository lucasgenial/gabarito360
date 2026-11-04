@extends('layouts.app')
@section('title','Área do conhecimento')
@section('breadcrumb')<a href="{{ route('disciplinas.index',$retorno) }}">Disciplinas</a><span class="sep">/</span><a href="{{ route('areas.index',$retorno) }}">Áreas do conhecimento</a><span class="sep">/</span><span>Consultar</span>@endsection
@section('content')
@php($a=$dados['area'])
@php($vinculos=$a['vinculos']??[])
@php($ativos=array_column(array_filter($vinculos,fn($p)=>$p['ativo']),'disciplina_id'))
@php($opcoes=array_filter($disciplinas,fn($d)=>$d['ativo']&&!in_array($d['id'],$ativos)))
<x-pagina :titulo="$a['nome']"><x-slot:sub>Sigla: {{ $a['sigla']?:'Não informada' }} · <span class="badge {{ $a['ativo']?'badge-success':'badge-info' }}">{{ $a['ativo']?'Ativa':'Inativa' }}</span></x-slot:sub><a class="btn btn-ghost" href="{{ route('areas.index',$retorno) }}">Voltar às áreas</a>@if($dados['gerir'])<a class="btn btn-primary" href="{{ route('areas.edit',['id'=>$a['id']]+$retorno) }}">Editar área</a>@endif</x-pagina>
@include('disciplinas._base')
<section class="card dc1-area-vinculos" aria-labelledby="area-historico">
  <div class="card-pad dc1-stack">
    <h2 id="area-historico" tabindex="-1" @if(session('foco_area')==='area-historico') data-dc1-foco @endif>Disciplinas desta área</h2>
    @if($dados['gerir'])
      @if($a['ativo'] && count($opcoes))
      <form method="POST" action="{{ route('areas.vincular',['id'=>$a['id']]) }}" class="dc1-vincular-area" data-dc1-envio>
        @csrf @include('disciplinas._retorno')<input type="hidden" name="versao" value="{{ $a['versao'] }}">
        <div class="field"><label for="area-disciplina">Disciplina</label><select class="select" id="area-disciplina" name="disciplina_selecao" required @if(session('foco_area')==='area-disciplina') data-dc1-foco @endif @error('disciplina_selecao') aria-invalid="true" aria-describedby="dc1-erro-disciplina_selecao" @enderror><option value="">Selecione uma disciplina</option>@foreach($opcoes as $d)<option value="{{ $d['id'].':'.$d['versao'] }}" @selected(old('disciplina_selecao')===$d['id'].':'.$d['versao'])>{{ $d['nome'] }}{{ $d['sigla']?' · '.$d['sigla']:'' }}</option>@endforeach</select>@include('disciplinas._erro',['campo'=>'disciplina_selecao'])</div>
        <button type="submit" class="btn btn-primary">Vincular</button>
      </form>
      @else<p class="field-help">{{ $a['ativo']?'Todas as disciplinas ativas disponíveis já estão vinculadas.':'Reative a área para vincular novas disciplinas.' }}</p>@endif
      <x-orientacao assunto="vínculos desta área"><p>Vincular organiza a disciplina nesta área. Não duplica seu cadastro nem altera as permissões dos professores.</p><p>Desvincular encerra apenas esta ligação. Os registros anteriores permanecem preservados. Vincular novamente inicia outro período no histórico.</p></x-orientacao>
    @endif
  <x-tabela titulo="Histórico de vínculos das disciplinas" modo="compacta" :completa="true"><table class="table tabela-padrao tabela-compacta dc1-vinculos-tabela"><caption class="sr-only">Vínculos ativos e encerrados das disciplinas nesta área</caption><thead><tr><th scope="col" data-ordenar="texto">Nome/dados</th><th scope="col">Ações</th></tr></thead><tbody>
    @forelse($vinculos as $p)
    <tr><td data-valor-ordem="{{ $p['nome'] }}"><a class="dc1-vinculo-nome" href="{{ route('disciplinas.show',['id'=>$p['disciplina_id']]+$retorno) }}">{{ $p['nome'] }}</a><span class="dc1-vinculo-situacao">{{ $p['ativo']?'Vínculo ativo':'Vínculo encerrado' }}{{ !$p['disciplina_ativa']?' · Disciplina inativa':'' }}</span>
      <div class="dc1-vinculo-data">Data da vinculação: @if($p['vinculado_em'])@php($inicio=\Carbon\CarbonImmutable::parse($p['vinculado_em'],'UTC')->setTimezone('America/Sao_Paulo'))<time datetime="{{ $inicio->toIso8601String() }}">{{ $inicio->format('d/m/Y · H:i') }}</time>@else<span>Não registrada</span>@endif</div>
      @if($p['desvinculado_em'])@php($fim=\Carbon\CarbonImmutable::parse($p['desvinculado_em'],'UTC')->setTimezone('America/Sao_Paulo'))<div class="dc1-vinculo-data">Data da desvinculação: <time datetime="{{ $fim->toIso8601String() }}">{{ $fim->format('d/m/Y · H:i') }}</time></div>@endif
    </td><td class="tabela-acoes">
      @if($dados['gerir'] && $p['ativo'])<form method="POST" action="{{ route('areas.desvincular',['id'=>$a['id']]) }}" data-dc1-envio>@csrf @include('disciplinas._retorno')<input type="hidden" name="versao" value="{{ $a['versao'] }}"><input type="hidden" name="disciplina_id" value="{{ $p['disciplina_id'] }}"><input type="hidden" name="disciplina_versao" value="{{ $p['disciplina_versao'] }}"><input type="hidden" name="vinculo_id" value="{{ $p['id'] }}"><button class="btn btn-secondary btn-sm" id="desvincular-{{ $p['id'] }}" type="submit" aria-label="Desvincular {{ $p['nome'] }} desta área">Desvincular</button></form>
      @else<span aria-label="Sem ações disponíveis">—</span>@endif
    </td></tr>
    @empty<tr><td colspan="2"><div class="dc1-vazio"><h3>Nenhuma disciplina vinculada</h3><p>{{ $dados['gerir']?'Selecione uma disciplina acima para iniciar.':'Consulte a coordenação sobre as disciplinas disponíveis no seu vínculo.' }}</p></div></td></tr>@endforelse
  </tbody></table></x-tabela>
  </div>
</section>
@include('disciplinas.areas._desvincular')
@endsection
