@extends('layouts.app')
@section('title','Selecionar resultados')
@section('content')
@php($ctx=\App\Support\ContextoWeb::id())
@php($lista=$dados['lista'])
<x-pagina titulo="Selecionar resultados"><x-slot:sub>Confira as notas registradas e suas dependências antes de preparar uma solicitação.</x-slot:sub></x-pagina>
<x-aviso>Esta consulta não autoriza nem inicia uma cópia. A versão histórica original ainda não está comprovada.</x-aviso>
<form method="GET" action="{{ route('copias.resultados') }}" class="card card-pad" style="margin-top:20px;">
  <input type="hidden" name="_ctx" value="{{ $ctx }}">
  <div class="field"><label for="espaco">Espaço de origem</label><select class="select" name="espaco_id" id="espaco" @disabled(!$dados['espacos'])>
    @forelse($dados['espacos'] as $e)<option value="{{ $e['id'] }}" @selected($dados['espaco_id']===$e['id'])>{{ $e['nome'] }}</option>@empty<option>Nenhum espaço disponível</option>@endforelse
  </select></div><button class="btn btn-secondary" type="submit">Ver resultados</button>
</form>
<form method="POST" action="{{ route('copias.resultados.previa') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ $ctx }}"><input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}">
  <p>Selecione de 1 a 20 resultados desta página. Trocar a página ou o espaço limpa a seleção. A prévia verificará se os cartões e suas respostas estão finalizados.</p>
  <div style="overflow-x:auto;"><table class="table"><caption>{{ $lista['total'] }} resultado(s) neste espaço</caption>
    <thead><tr><th scope="col">Selecionar</th><th scope="col">Aluno</th><th scope="col">Matrícula</th><th scope="col">Prova</th><th scope="col">Turma</th><th scope="col">Nota registrada</th></tr></thead>
    <tbody>@forelse($lista['data'] as $r)<tr><td><input type="checkbox" id="nota-{{ $r['id'] }}" name="notas[]" value="{{ $r['id'] }}" aria-label="Selecionar resultado de {{ $r['aluno'] }} na prova {{ $r['prova'] }}"></td>
      <td><label for="nota-{{ $r['id'] }}">{{ $r['aluno'] }}</label></td><td>{{ $r['matricula'] }}</td><td>{{ $r['prova'] }}</td><td>{{ $r['turma'] }}</td><td>{{ $r['nota_final'] }}</td></tr>
    @empty<tr><td colspan="6">Nenhum resultado disponível.</td></tr>@endforelse</tbody>
  </table></div>
  <div class="field"><label for="tipo_destino">Tipo de destino para simulação</label><select class="select" id="tipo_destino" name="tipo_destino" required>
    <option value="">Selecione o tipo</option><option value="pessoal">Pessoal</option><option value="escola">Escolar</option>
  </select></div><p>Esta escolha não identifica um destinatário real.</p>
  <button class="btn btn-primary" type="submit" @disabled(!$lista['data'])>Conferir prévia dos resultados</button>
</form>
@if($lista['last_page']>1)<nav aria-label="Paginação dos resultados" style="margin-top:16px;">
  @php($f=['espaco_id'=>$dados['espaco_id'],'_ctx'=>$ctx])
  @if($lista['current_page']>1)<a href="{{ route('copias.resultados',$f+['page'=>$lista['current_page']-1]) }}">Anterior</a>@endif
  <span>Página {{ $lista['current_page'] }} de {{ $lista['last_page'] }}</span>
  @if($lista['current_page']<$lista['last_page'])<a href="{{ route('copias.resultados',$f+['page'=>$lista['current_page']+1]) }}">Próxima</a>@endif
</nav>@endif
@endsection
