@foreach($materiais as $item)
@php($p=$item['material']['prova'])
<section class="card card-pad" style="margin-top:20px;">
  <h2>{{ $p['titulo'] }}</h2>
  <dl><dt>Disciplina e série</dt><dd>{{ $p['disciplina'] }} · {{ $p['serie'] }}</dd>
    <dt>Formato</dt><dd>{{ $p['num_questoes'] }} questões · {{ $p['num_alternativas'] }} alternativas</dd>
    <dt>Nota máxima</dt><dd>{{ $p['nota_maxima'] }}</dd>
    <dt>Pontuação</dt><dd>{{ $p['tipo_pontuacao']==='personalizado'?'Personalizada':'Igual por questão' }}</dd>
    <dt>Anular se todas marcadas</dt><dd>{{ $p['anular_se_todas']?'Sim':'Não' }}</dd>
    <dt>Opção de língua</dt><dd>{{ $p['opcao_lingua']?'Sim':'Não' }}</dd></dl>
  <details><summary>Conferir gabarito</summary><div style="overflow-x:auto;">
    <x-tabela titulo="Conferência do gabarito" modo="lista"><table class="tabela-padrao table"><caption class="sr-only">Conferência do gabarito</caption><thead><tr><th scope="col">Questão</th><th scope="col">Alternativa</th><th scope="col">Espanhol</th><th scope="col">Peso</th><th scope="col">Anulada</th></tr></thead>
      <tbody>@foreach($item['material']['questoes'] as $q)<tr><td class="tabela-principal" data-rotulo="Questão"><div class="tabela-valor">{{ $q['numero_questao'] }}</div></td><td data-rotulo="Alternativa"><div class="tabela-valor">{{ $q['alternativa'] }}</div></td><td data-rotulo="Espanhol"><div class="tabela-valor">{{ $q['alternativa_espanhol'] ?? '—' }}</div></td><td data-rotulo="Peso"><div class="tabela-valor">{{ $q['peso'] }}</div></td><td data-rotulo="Anulada"><div class="tabela-valor">{{ $q['anulada']?'Sim':'Não' }}</div></td></tr>@endforeach</tbody>
    </table></x-tabela></div></details>
</section>
@endforeach
