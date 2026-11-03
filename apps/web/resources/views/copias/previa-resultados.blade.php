@extends('layouts.app')
@section('title','Prévia dos resultados')
@section('content')
<x-pagina titulo="Prévia dos resultados"><x-slot:sub>Simulação para destino {{ $dados['tipo_destino']==='escola'?'escolar':'pessoal' }}.</x-slot:sub></x-pagina>
<x-aviso>A versão histórica original não está comprovada. A nota abaixo é a registrada no sistema: não foi recalculada nem atribuída ao gabarito atual.</x-aviso>
<p>Esta consulta não preserva um pacote histórico, não identifica destinatário e não autoriza uma transferência. Ainda serão necessárias a confirmação das dependências, evidência aplicável, guarda contratual, reconhecimento do legado e decisões separadas.</p>
@foreach($dados['resultados'] as $r)
<section class="card card-pad" style="margin-top:20px;">
  <h2>{{ $r['aluno'] }} — {{ $r['prova'] }}</h2>
  <dl><dt>Matrícula</dt><dd>{{ $r['matricula'] }}</dd><dt>Turma</dt><dd>{{ $r['turma'] }}</dd>
    <dt>Nota registrada</dt><dd>{{ $r['dados']['nota_final'] }}</dd><dt>Acertos registrados</dt><dd>{{ $r['dados']['acertos'] }} de {{ $r['dados']['total_questoes'] }}</dd>
    <dt>Situação registrada</dt><dd>{{ $r['dados']['status_aprovacao']==='aprovado'?'Aprovado':'Recuperação' }}</dd>
    <dt>Língua</dt><dd>{{ ['ING'=>'Inglês','ESP'=>'Espanhol'][$r['dados']['lingua']??''] ?? 'Não informada' }}</dd></dl>
</section>
@endforeach
<section class="card card-pad" style="margin-top:20px;">
  <h2>Dependências da seleção</h2><p>As listas abaixo identificam os registros associados. Exibi-las não equivale a confirmar sua transferência.</p>
  <h3>Provas</h3><ul>@foreach($dados['dependencias']['provas'] as $p)<li>{{ $p['titulo'] }}</li>@endforeach</ul>
  <h3>Turmas</h3><ul>@foreach($dados['dependencias']['turmas'] as $t)<li>{{ $t['nome'] }}</li>@endforeach</ul>
  <h3>Alunos</h3><ul>@foreach($dados['dependencias']['alunos'] as $a)<li>{{ $a['nome'] }} — {{ $a['matricula'] }}</li>@endforeach</ul>
</section>
<a class="btn btn-secondary" style="margin-top:20px;" href="{{ route('copias.resultados',['espaco_id'=>$dados['espaco_id'],'_ctx'=>\App\Support\ContextoWeb::id()]) }}">Voltar e fazer nova seleção</a>
<form method="POST" action="{{ route('copias.resultado.preparar') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ \App\Support\ContextoWeb::id() }}"><input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}">
  @foreach($dados['resultados'] as $r)<input type="hidden" name="notas[]" value="{{ $r['id'] }}">@endforeach
  <div class="field"><label for="codigo">Código de resultados fornecido pelo destinatário</label><input class="input" id="codigo" name="codigo" required minlength="64" maxlength="64" autocomplete="off" spellcheck="false"></div>
  @include('copias._codigo-destino',['modalidade'=>'resultado'])
  <button class="btn btn-primary" type="submit">Conferir pacote e destino</button>
</form>
@endsection
