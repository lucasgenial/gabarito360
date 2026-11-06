@extends('layouts.app')
@section('title',$dados['componente']['nome'].' — '.$turma['nome'])
@section('breadcrumb')<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span><a href="{{ route('turmas.show',$turma['id']) }}">{{ $turma['nome'] }}</a><span class="sep">/</span><span>{{ $dados['componente']['nome'] }}</span>@endsection
@section('content')
@php($c=$dados['componente'])
@php($hoje=now('America/Sao_Paulo')->toDateString())
@php($voltar=['retorno_turma'=>$turma['id'],'retorno_componente'=>$c['id']]+$retorno)
<x-pagina :titulo="$c['nome'].' · '.$turma['nome']"><x-slot:sub>Disciplinas e professores · Ano letivo {{ $turma['ano_letivo'] }}</x-slot:sub><a class="btn btn-secondary" href="{{ route('turmas.show',$turma['id']) }}#disciplinas">Voltar à turma</a></x-pagina>
@include('disciplinas._base')
<div class="dc1-stack">
<section class="card card-pad" aria-label="Situação da disciplina na turma">
  <dl class="dc1-meta"><div><dt>Nesta turma</dt><dd>{{ ($c['ativo']??true)?'Ativa':'Inativa' }}</dd></div><div><dt>No catálogo da escola</dt><dd>{{ $c['disciplina_ativa']?'Ativa':'Inativa' }}</dd></div></dl>
  @if($dados['gerir'])
  @php($abertas=collect($c['atribuicoes'])->contains(fn($a)=>in_array($a['estado_periodo'],['Em curso','Programada'])))
  @php($podeAlterar=($c['ativo']??true)?!$abertas:($c['disciplina_ativa']&&$turma['ativo']))
  <form class="dc1-acoes dc1-ajuda" method="POST" action="{{ route('disciplinas.situacao',['turma'=>$turma['id'],'componente'=>$c['id']]) }}" data-dc1-titulo="{{ ($c['ativo']??true)?'Inativar':'Reativar' }} {{ $c['nome'] }} na turma {{ $turma['nome'] }}" data-dc1-verbo="{{ ($c['ativo']??true)?'Inativar nesta turma':'Reativar nesta turma' }}" data-dc1-confirmar="{{ ($c['ativo']??true)?'A disciplina deixará de receber novas atribuições nesta turma. O histórico será preservado. O catálogo e as outras turmas não serão alterados.':'Reativar esta disciplina somente nesta turma?' }}">@csrf <x-revisao-acesso /><input type="hidden" name="versao" value="{{ $c['versao'] }}"><input type="hidden" name="ativo" value="{{ ($c['ativo']??true)?0:1 }}"><button class="btn btn-secondary" type="submit" @disabled(!$podeAlterar||session('conflito_dc1'))>{{ ($c['ativo']??true)?'Inativar nesta turma':'Reativar nesta turma' }}</button>
  @unless($podeAlterar)<p class="field-help">@if($abertas)Encerre ou cancele as <a href="#atribuicoes-titulo">atribuições abertas</a> antes de inativar.@else A turma e a disciplina do catálogo precisam estar ativas para reativar.@endif</p>@endunless
  </form>
  @endif
</section>
<section class="card" aria-labelledby="atribuicoes-titulo"><div class="card-pad"><h2 id="atribuicoes-titulo">{{ \App\Support\Acesso::perfil()==='professor'?'Minhas atribuições':'Atribuições nesta turma' }}</h2><p class="dc1-texto">Acompanhe os professores, os períodos e o histórico desta disciplina.</p></div>
<x-tabela :interna="true" :integrada="true" titulo="Atribuições docentes"><table class="tabela-padrao"><caption class="sr-only">Atribuições em {{ $c['nome'] }} na turma {{ $turma['nome'] }}</caption><thead><tr><th scope="col">Professor</th><th scope="col">Atuação</th><th scope="col">Período</th><th scope="col">Situação</th>@if($dados['atribuir'])<th scope="col">Ações</th>@endif</tr></thead><tbody>
@forelse($c['atribuicoes'] as $a)
<tr><td class="tabela-principal" data-rotulo="Professor"><div class="tabela-valor">{{ $a['nome'] }}</div>@unless($a['vinculo_vigente'])<span class="dc1-erro-vinculo">Vínculo atualmente indisponível</span>@endunless</td><td data-rotulo="Atuação">{{ ['titular'=>'Titular','codocencia'=>'Codocência','substituicao'=>'Substituição'][$a['papel']] }}</td><td data-rotulo="Período"><div class="tabela-valor">Desde {{ \Carbon\Carbon::parse($a['inicio_em'])->format('d/m/Y') }}<span class="dc1-periodo">{{ $a['fim_em']?'Não atua a partir de '.\Carbon\Carbon::parse($a['fim_em'])->format('d/m/Y'):'Sem encerramento definido' }}</span></div></td><td data-rotulo="Situação"><span class="badge {{ $a['estado_periodo']==='Em curso'?'badge-success':'badge-info' }}">{{ $a['estado_periodo'] }}</span></td>
@if($dados['atribuir'])<td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor">@if(in_array($a['estado_periodo'],['Programada','Em curso']))<form method="POST" action="{{ route('disciplinas.encerrar',['turma'=>$turma['id'],'componente'=>$c['id'],'atribuicao'=>$a['id']]) }}" data-dc1-titulo="{{ $a['estado_periodo']==='Programada'?'Cancelar':'Encerrar' }} atribuição de {{ $a['nome'] }} em {{ $c['nome'] }} · {{ $turma['nome'] }}" data-dc1-verbo="{{ $a['estado_periodo']==='Programada'?'Cancelar atribuição':'Encerrar a partir de hoje' }}" data-dc1-confirmar="{{ $a['estado_periodo']==='Programada'?'A atribuição programada será cancelada. O histórico será preservado.':'O professor deixará de atuar nesta atribuição a partir de hoje. O histórico será preservado.' }}">@csrf <x-revisao-acesso /><input type="hidden" name="versao" value="{{ $a['versao'] }}"><input type="hidden" name="acao" value="{{ $a['estado_periodo']==='Programada'?'cancelar':'encerrar' }}"><button type="submit" class="btn btn-ghost btn-sm" @disabled(session('conflito_dc1'))>{{ $a['estado_periodo']==='Programada'?'Cancelar atribuição':'Encerrar a partir de hoje' }}<span class="sr-only"> de {{ $a['nome'] }}</span></button></form>@else<span>Histórico preservado</span>@endif</div></td>@endif
</tr>
@empty<tr><td colspan="{{ $dados['atribuir']?5:4 }}"><div class="dc1-vazio"><h3>Sem professor atribuído</h3><p>{{ $dados['atribuir']?'A disciplina já está na turma. Escolha abaixo quem irá lecionar e em qual período.':'As atribuições são organizadas pela coordenação.' }}</p></div></td></tr>@endforelse
</tbody></table></x-tabela></section>
@if($dados['atribuir'])
<section class="card card-pad" aria-labelledby="atribuir-titulo">
  <h2 id="atribuir-titulo">Atribuir professor</h2>
  @if(!$c['disciplina_ativa'] || !$turma['ativo'] || !($c['ativo']??true))<p class="dc1-texto">A turma, a disciplina do catálogo e sua ativação nesta turma precisam estar ativas para receber novas atribuições.</p>
  @else
  <p class="dc1-texto">A lista reúne professores com vínculo ativo, acesso a esta turma e indicação nesta disciplina.</p>
  @if(count($dados['elegiveis']))
  <form method="POST" action="{{ route('disciplinas.atribuir',['turma'=>$turma['id'],'componente'=>$c['id']]) }}" class="dc1-stack" id="dc1-atribuicao">
    @csrf <x-revisao-acesso /><input type="hidden" name="versao" value="{{ old('versao',$c['versao']) }}"><input type="hidden" name="chave" value="{{ old('chave',$retorno['rascunho_chave']??(string)\Illuminate\Support\Str::uuid()) }}">
    <div class="dc1-grade">
      <div class="field"><label for="atribuir-professor">Professor <span aria-hidden="true">*</span></label><select id="atribuir-professor" name="vinculo_id" class="select" required @error('vinculo_id') aria-invalid="true" aria-describedby="dc1-erro-vinculo_id" @enderror><option value="">Selecione um professor</option>@foreach($dados['elegiveis'] as $p)<option value="{{ $p['vinculo_id'] }}" @selected((string)old('vinculo_id',$retorno['rascunho_vinculo']??'')===(string)$p['vinculo_id'])>{{ $p['nome'] }}</option>@endforeach</select>@include('disciplinas._erro',['campo'=>'vinculo_id'])</div>
      <div class="field"><label for="atribuicao-papel">Atuação</label><select id="atribuicao-papel" name="papel" class="select" @error('papel') aria-invalid="true" aria-describedby="dc1-erro-papel" @enderror>@foreach(['titular'=>'Titular','codocencia'=>'Codocência','substituicao'=>'Substituição'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(old('papel',$retorno['rascunho_papel']??'titular')===$valor)>{{ $rotulo }}</option>@endforeach</select>@include('disciplinas._erro',['campo'=>'papel'])<p class="field-help">Codocência e substituição permitem atuação junto a outro professor. Nenhuma opção encerra automaticamente uma atribuição existente.</p></div>
      <div class="field"><label for="atribuicao-inicio">Início <span aria-hidden="true">*</span></label><input id="atribuicao-inicio" type="date" class="input" name="inicio_em" min="{{ $hoje }}" required value="{{ old('inicio_em',$retorno['rascunho_inicio']??$hoje) }}" @error('inicio_em') aria-invalid="true" aria-describedby="dc1-erro-inicio_em" @enderror>@include('disciplinas._erro',['campo'=>'inicio_em'])</div>
      <div class="field"><label for="atribuicao-fim">Encerramento <span class="field-help">(opcional)</span></label><input id="atribuicao-fim" type="date" class="input" name="fim_em" min="{{ $hoje }}" value="{{ old('fim_em',$retorno['rascunho_fim']??'') }}" @error('fim_em') aria-invalid="true" aria-describedby="dc1-erro-fim_em" @enderror>@include('disciplinas._erro',['campo'=>'fim_em'])<p class="field-help">A partir desta data, o professor não atua mais nesta atribuição.</p></div>
    </div>
    <div class="dc1-acoes"><button type="submit" class="btn btn-primary" @disabled(session('conflito_dc1'))>Confirmar atribuição</button></div>
  </form>
  @else
  @php($indicadas=collect($dados['indicacoes'])->where('ativo',true))
  @php($vigentes=$indicadas->where('vinculo_vigente',true))
  <div class="dc1-vazio"><h3>{{ $indicadas->isEmpty()?'Nenhum professor indicado nesta disciplina':($vigentes->isEmpty()?'Os vínculos indicados estão indisponíveis':'Os professores indicados ainda não têm acesso a esta turma') }}</h3><p>{{ $indicadas->isEmpty()?'Adicione um professor à disciplina. Depois, confira o acesso dele à turma.':($vigentes->isEmpty()?'Confira na equipe o aceite, a situação e a vigência do vínculo Professor.':'Gerir o acesso à turma é uma ação separada. A atribuição não concede esse acesso automaticamente.') }}</p></div>
  @endif
  <details class="dc1-ajuda" @if(!count($dados['elegiveis'])) open @endif><summary>Não encontrou o professor?</summary><div class="dc1-acoes">
    <a class="btn btn-secondary" data-dc1-retorno href="{{ route('disciplinas.show',['id'=>$c['disciplina_id']]+$voltar) }}#adicionar-professor">Adicionar à disciplina</a>
    @if(\App\Support\Acesso::pode('turma.vincular_professor'))<a class="btn btn-secondary" data-dc1-retorno href="{{ route('turmas.show',['id'=>$turma['id']]+$voltar) }}#professores">Gerir acesso à turma</a>@endif
  </div></details>
  @endif
</section>
@endif
</div>

@endsection
