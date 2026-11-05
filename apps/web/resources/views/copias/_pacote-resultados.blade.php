<x-aviso>Legado sem versão original comprovada. A nota armazenada não foi recalculada. O material abaixo foi observado ao preparar o pacote e não comprova qual gabarito foi usado na correção original.</x-aviso>
@foreach($pacote['resultados'] as $r)
<section class="card card-pad" style="margin-top:20px;">
  <h2>Resultado {{ $loop->iteration }}: {{ $r['aluno'] }} — {{ $r['prova'] }}</h2>
  <dl><dt>Matrícula</dt><dd>{{ $r['matricula'] }}</dd><dt>Turma de aplicação</dt><dd>{{ $r['turma'] }}</dd>
    <dt>Nota armazenada</dt><dd>{{ $r['dados']['nota_final'] }}</dd><dt>Acertos armazenados</dt><dd>{{ $r['dados']['acertos'] }} de {{ $r['dados']['total_questoes'] }}</dd>
    <dt>Situação armazenada</dt><dd>{{ $r['dados']['status_aprovacao']==='aprovado'?'Aprovado':'Recuperação' }}</dd>
    <dt>Língua</dt><dd>{{ ['ING'=>'Inglês','ESP'=>'Espanhol'][$r['dados']['lingua']??''] ?? 'Não informada' }}</dd></dl>
  <details><summary>Conferir respostas incluídas</summary><x-tabela :integrada="true" titulo="Conferência das respostas" modo="lista"><table class="tabela-padrao table"><caption class="sr-only">Conferência das respostas</caption><thead><tr><th scope="col">Questão</th><th scope="col">Resposta</th></tr></thead>
    <tbody>@foreach($r['respostas'] as $q)<tr><td class="tabela-principal" data-rotulo="Questão"><div class="tabela-valor">{{ $q['numero_questao'] }}</div></td><td data-rotulo="Resposta"><div class="tabela-valor">{{ $q['alternativa'] ?? 'Em branco' }}</div></td></tr>@endforeach</tbody>
  </table></x-tabela></details>
</section>
@endforeach
<section class="card card-pad" style="margin-top:20px;">
  <h2>Dependências incluídas no pacote</h2>
  <h3>Alunos</h3><ul>@foreach($pacote['alunos'] as $a)<li>{{ $a['nome'] }} — {{ $a['matricula'] }}</li>@endforeach</ul>
  <h3>Turmas de aplicação</h3><ul>@foreach($pacote['turmas'] as $t)<li>{{ $t['nome'] }} · Série {{ $t['serie'] }} · {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['turno']] ?? $t['turno'] }} · {{ $t['ano_letivo'] }}</li>@endforeach</ul>
  <h3>Materiais observados</h3><p>Confira os campos e gabaritos de todas as provas abaixo.</p>
</section>
@include('copias._materiais',['materiais'=>$pacote['materiais']])
