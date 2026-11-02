@if(($dados['tem_pendencias']??false) || !empty($dados['pendencias']))<x-aviso tipo="erro">Há data de nascimento ausente na seleção. O cadastro precisará de complementação autorizada antes de uma cópia para escola.</x-aviso>@endif
@foreach($dados['turmas'] as $t)
<section class="card card-pad" style="margin-top:20px;"><h2>{{ $t['dados']['nome'] }}</h2>
  <dl><dt>Série</dt><dd>{{ $t['dados']['serie'] }}</dd><dt>Turno</dt><dd>{{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['dados']['turno']] }}</dd>
    <dt>Ano letivo</dt><dd>{{ $t['dados']['ano_letivo'] }}</dd><dt>Ativa</dt><dd>{{ $t['dados']['ativo']?'Sim':'Não' }}</dd></dl>
</section>
@endforeach
<section class="card card-pad" style="margin-top:20px;overflow-x:auto;">
  <table class="table"><caption>{{ count($dados['alunos']) }} aluno(s) selecionado(s)</caption>
    <thead><tr><th scope="col">Nome</th><th scope="col">Matrícula</th><th scope="col">Avatar</th><th scope="col">Ativo</th>@if($dados['tipo_destino']==='escola')<th scope="col">Nascimento</th>@endif</tr></thead>
    <tbody>@forelse($dados['alunos'] as $a)<tr><td>{{ $a['dados']['nome'] }}</td><td>{{ $a['dados']['matricula'] }}</td><td>{{ $a['dados']['avatar_id'] ?? 'Não informado' }}</td><td>{{ $a['dados']['ativo']?'Sim':'Não' }}</td>
      @if($dados['tipo_destino']==='escola')<td>{{ isset($a['dados']['data_nascimento'])?\Carbon\Carbon::parse($a['dados']['data_nascimento'])->format('d/m/Y'):'Pendente — não informado' }}</td>@endif</tr>
    @empty<tr><td colspan="{{ $dados['tipo_destino']==='escola'?5:4 }}">Somente a turma foi selecionada. Nenhum aluno foi incluído automaticamente.</td></tr>@endforelse</tbody>
  </table>
</section>
