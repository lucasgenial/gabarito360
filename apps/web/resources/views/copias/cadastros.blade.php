@extends('layouts.app')
@section('title','Selecionar cadastro')
@section('content')
@php($ctx=\App\Support\ContextoWeb::id())
@php($f=['espaco_id'=>$dados['espaco_id'],'_ctx'=>$ctx])
<x-pagina titulo="Selecionar cadastro"><x-slot:sub>Confira uma turma e seus alunos antes de preparar uma solicitação.</x-slot:sub></x-pagina>
<x-aviso>Esta consulta não autoriza nem inicia uma cópia. Trocar o espaço, a turma ou a página limpa a seleção.</x-aviso>
<form method="GET" action="{{ route('copias.cadastros') }}" class="card card-pad" style="margin-top:20px;">
  <input type="hidden" name="_ctx" value="{{ $ctx }}">
  <div class="field"><label for="espaco">Espaço de origem</label><select class="select" id="espaco" name="espaco_id" @disabled(!$dados['espacos'])>
    @forelse($dados['espacos'] as $e)<option value="{{ $e['id'] }}" @selected($dados['espaco_id']===$e['id'])>{{ $e['nome'] }}</option>@empty<option>Nenhum espaço disponível</option>@endforelse
  </select></div><button class="btn btn-secondary" type="submit">Ver turmas</button>
</form>
<section class="card card-pad" style="margin-top:20px;overflow-x:auto;">
  <x-tabela :integrada="true" titulo="Turmas disponíveis" modo="lista"><table class="tabela-padrao table"><caption>{{ $dados['turmas']['total'] }} turma(s) neste espaço</caption>
    <thead><tr><th scope="col">Turma</th><th scope="col">Série</th><th scope="col">Ano</th><th scope="col">Consultar</th></tr></thead>
    <tbody>@forelse($dados['turmas']['data'] as $t)<tr><td class="tabela-principal" data-rotulo="Turma"><div class="tabela-valor">{{ $t['nome'] }}</div></td><td data-rotulo="Série"><div class="tabela-valor">{{ $t['serie'] }}</div></td><td data-rotulo="Ano"><div class="tabela-valor">{{ $t['ano_letivo'] }}</div></td>
      <td class="tabela-acoes" data-rotulo="Consultar"><div class="tabela-valor"><a href="{{ route('copias.cadastros',$f+['turma_id'=>$t['id'],'page'=>$dados['turmas']['current_page']]) }}" aria-label="Abrir turma {{ $t['nome'] }}">Abrir turma</a></div></td></tr>
    @empty<tr><td colspan="4">Nenhuma turma disponível.</td></tr>@endforelse</tbody>
  </table></x-tabela>
  @if($dados['turmas']['last_page']>1)<nav aria-label="Paginação das turmas">
    @if($dados['turmas']['current_page']>1)<a href="{{ route('copias.cadastros',$f+['page'=>$dados['turmas']['current_page']-1]) }}">Turmas anteriores</a>@endif
    <span>Página {{ $dados['turmas']['current_page'] }} de {{ $dados['turmas']['last_page'] }}</span>
    @if($dados['turmas']['current_page']<$dados['turmas']['last_page'])<a href="{{ route('copias.cadastros',$f+['page'=>$dados['turmas']['current_page']+1]) }}">Próximas turmas</a>@endif
  </nav>@endif
</section>
@if($dados['turma'])
<form method="POST" action="{{ route('copias.cadastros.previa') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ $ctx }}"><input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}">
  <h2>{{ $dados['turma']['nome'] }}</h2>
  <p><label><input type="checkbox" name="turmas[]" value="{{ $dados['turma']['id'] }}" required> Incluir esta turma na prévia.</label></p>
  <p>Marque somente os alunos que deseja incluir. A turma precisa ser confirmada mesmo quando houver alunos selecionados. Também é possível conferir apenas a turma.</p>
  <div style="overflow-x:auto;"><x-tabela :integrada="true" titulo="Selecionar alunos" modo="selecao"><table class="tabela-padrao table"><caption>{{ $dados['alunos']['total'] }} aluno(s) nesta turma</caption>
    <thead><tr><th scope="col">Selecionar</th><th scope="col">Aluno</th><th scope="col">Matrícula</th></tr></thead>
    <tbody>@forelse($dados['alunos']['data'] as $a)<tr><td class="tabela-selecionar" data-rotulo="Selecionar"><div class="tabela-valor"><input type="checkbox" id="aluno-{{ $a['id'] }}" name="alunos[]" value="{{ $a['id'] }}" aria-label="Selecionar {{ $a['nome'] }}"></div></td>
      <td class="tabela-principal" data-rotulo="Aluno"><div class="tabela-valor"><label for="aluno-{{ $a['id'] }}">{{ $a['nome'] }}</label></div></td><td data-rotulo="Matrícula"><div class="tabela-valor">{{ $a['matricula'] }}</div></td></tr>
    @empty<tr><td colspan="3">Nenhum aluno nesta turma.</td></tr>@endforelse</tbody>
  </table></x-tabela></div>
  <div class="field"><label for="tipo_destino">Tipo de destino para simular os campos</label><select class="select" id="tipo_destino" name="tipo_destino" required>
    <option value="">Selecione o tipo</option><option value="pessoal">Pessoal</option><option value="escola">Escolar</option>
  </select></div><p>Esta escolha não identifica um destinatário real.</p>
  <button class="btn btn-primary" type="submit">Conferir prévia do cadastro</button>
</form>
@if($dados['alunos']['last_page']>1)<nav aria-label="Paginação dos alunos" style="margin-top:16px;">
  @php($base=$f+['turma_id'=>$dados['turma']['id'],'page'=>$dados['turmas']['current_page']])
  @if($dados['alunos']['current_page']>1)<a href="{{ route('copias.cadastros',$base+['alunos_page'=>$dados['alunos']['current_page']-1]) }}">Alunos anteriores</a>@endif
  <span>Página {{ $dados['alunos']['current_page'] }} de {{ $dados['alunos']['last_page'] }}</span>
  @if($dados['alunos']['current_page']<$dados['alunos']['last_page'])<a href="{{ route('copias.cadastros',$base+['alunos_page'=>$dados['alunos']['current_page']+1]) }}">Próximos alunos</a>@endif
</nav>@endif
@endif
@endsection
