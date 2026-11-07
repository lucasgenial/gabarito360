@if($painelErro??false)<x-aviso tipo="erro">Não foi possível carregar os indicadores da turma. Atualize a página para tentar novamente.</x-aviso>
@elseif($painel)
@php
$k=$painel['kpis']; $analise=$painel['analise']??[]; $resumo=$analise['resumo']??[];
$filtroValor=fn($nome)=>is_scalar(request($nome))?(string)request($nome):'';
$painelAplicado=$painelAplicado??[]; $painelErros=$painelErros??[];
$situacoes=$painel['situacoes_resultados']??[]; $acompanhamento=$painel['acompanhamento']??null;
$questoes=$analise['questoes']??[];
$numero=fn($v)=>$v===null?'—':number_format($v,1,',','').'%';
$indicadores=[
 ['Avaliações aplicadas',$k['avaliacoes'],'prova','azul',($k['avaliacoes_sem_resultado']??0).' sem resultado concluído'],
 ['Resultados concluídos',$k['resultados'],'confirmar','verde','Resultados no recorte selecionado'],
 ['Alunos com resultado',$k['alunos_com_resultado']??0,'turma','violeta','Pessoas distintas com resultado'],
 ['Acerto médio',$numero($resumo['media_pct']??$k['acerto_pct']??null),'grafico','azul',($resumo['n']??$k['resultados_com_percentual']??0).' resultados na base'],
 ['Mediana',$numero($resumo['mediana_pct']??null),'grafico','turquesa','Ponto central dos percentuais'],
 ['Faixa observada',isset($resumo['min_pct'])?$numero($resumo['min_pct']).' – '.$numero($resumo['max_pct']):'—','grafico','ambar','Do menor ao maior percentual'],
];
@endphp
<dialog class="tu1-dialog" data-painel-modal data-painel-erros="{{ count($painelErros) }}" aria-labelledby="painel-filtros-titulo">
<div class="tu1-dialog-cabecalho"><h2 id="painel-filtros-titulo">Filtrar indicadores</h2><button class="btn btn-ghost tu1-icone-acao" type="button" data-painel-cancelar aria-label="Fechar filtros" title="Fechar filtros"><x-icone nome="fechar" /></button></div>
<form method="GET" action="{{ route('turmas.show',$turma['id']) }}" data-painel-filtros>
<input type="hidden" name="secao" value="visao-geral">
@foreach($painelAplicado as $campo=>$valor)<input type="hidden" name="painel_anterior[{{ $campo }}]" value="{{ $valor }}">@endforeach
@if($painelErros)<p role="alert" class="tu1-erro">Confira os filtros. Os indicadores abaixo mantêm o recorte anterior.</p>@endif
<div class="form-row-2">
<div class="field"><label for="painel-disciplina">Disciplina da avaliação</label><select id="painel-disciplina" class="select" name="painel_disciplina" @if(isset($painelErros['painel_disciplina'])) aria-invalid="true" aria-describedby="erro-painel-disciplina" @endif><option value="">Todas as disciplinas</option>@foreach($painel['opcoes']['disciplinas'] as $d)<option value="{{ $d['id'] }}" @selected(request('painel_disciplina')==$d['id'])>{{ $d['nome'] }}</option>@endforeach</select>@if(isset($painelErros['painel_disciplina']))<p class="tu1-erro" id="erro-painel-disciplina">{{ $painelErros['painel_disciplina'][0] }}</p>@endif</div>
<div class="field"><label for="painel-professor">Professor autor</label><select id="painel-professor" class="select" name="painel_professor" @if(isset($painelErros['painel_professor'])) aria-invalid="true" aria-describedby="erro-painel-professor" @endif><option value="">Todos os autores autorizados</option>@foreach($painel['opcoes']['professores'] as $p)<option value="{{ $p['id'] }}" @selected(request('painel_professor')==$p['id'])>{{ $p['nome'] }}</option>@endforeach</select>@if(isset($painelErros['painel_professor']))<p class="tu1-erro" id="erro-painel-professor">{{ $painelErros['painel_professor'][0] }}</p>@endif</div>
<div class="field"><label for="painel-inicio">Aplicadas a partir de</label><input id="painel-inicio" class="input" type="date" name="painel_inicio" @if(isset($painelErros['painel_inicio'])) aria-invalid="true" aria-describedby="erro-painel-inicio" @endif value="{{ $filtroValor('painel_inicio') }}">@if(isset($painelErros['painel_inicio']))<p class="tu1-erro" id="erro-painel-inicio">{{ $painelErros['painel_inicio'][0] }}</p>@endif</div>
<div class="field"><label for="painel-fim">Aplicadas até</label><input id="painel-fim" class="input" type="date" name="painel_fim" @if(isset($painelErros['painel_fim'])) aria-invalid="true" aria-describedby="erro-painel-fim" @endif value="{{ $filtroValor('painel_fim') }}">@if(isset($painelErros['painel_fim']))<p class="tu1-erro" id="erro-painel-fim">{{ $painelErros['painel_fim'][0] }}</p>@endif</div>
</div>
<details class="dc1-ajuda"><summary>Saiba mais sobre os filtros</summary><p>O período usa a data de aplicação; avaliações sem data ficam fora quando há um limite de período. Professor autor é quem criou a avaliação atuando como professor, não necessariamente o docente atual. Disciplina seleciona a avaliação inteira: uma avaliação multidisciplinar não tem sua nota dividida por disciplina.</p></details>
<div class="tu1-acoes"><button class="btn btn-secondary" type="button" data-painel-limpar><x-icone nome="limpar" /> Limpar</button><button class="btn btn-secondary" type="button" data-painel-cancelar><x-icone nome="fechar" /> Cancelar</button><button class="btn btn-primary" type="submit"><x-icone nome="confirmar" /> Aplicar filtros</button></div>
</form></dialog>

<div class="tu1-indicadores tu1r3-kpis">
@foreach($indicadores as [$rotulo,$valor,$icone,$tom,$ajuda])
<div class="card kpi tu1-indicador tu1-tom-{{ $tom }}"><div class="tu1-kpi-topo"><span class="kpi-label">{{ $rotulo }}</span><x-icone :nome="$icone" /></div><div class="kpi-value">@if($rotulo==='Faixa observada'&&isset($resumo['min_pct']))<span class="tu1r3-nowrap">{{ $numero($resumo['min_pct']) }}</span> – <span class="tu1r3-nowrap">{{ $numero($resumo['max_pct']) }}</span>@else{{ $valor }}@endif</div><p class="field-help">{{ $ajuda }}</p></div>
@endforeach
</div>
<div class="tu1r3-graficos" data-painel-graficos data-painel-dados="{{ json_encode(['analise'=>$analise,'situacoes'=>$situacoes],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) }}">
<article class="card card-pad tu1r3-grafico-card"><h2>Distribuição dos acertos</h2><p class="field-help">Quantos resultados estão em cada faixa de acerto · base {{ $resumo['n']??0 }}.</p><div class="tu1r3-grafico" data-grafico="distribuicao" role="img" aria-label="Distribuição dos resultados por faixa de acerto"></div><details class="tu1r3-dados"><summary>Consultar valores</summary><ul>@foreach($analise['distribuicao_acertos']['faixas']??[] as $f)<li>{{ $f['label'] }}: <strong>{{ $f['total'] }}</strong></li>@endforeach</ul></details></article>
<article class="card card-pad tu1r3-grafico-card"><h2>Resultados por avaliação</h2><p class="field-help">Média e mediana dos acertos · cada avaliação mantém sua base.</p><div class="tu1r3-grafico" data-grafico="avaliacoes" role="img" aria-label="Média e mediana por avaliação"></div><details class="tu1r3-dados"><summary>Consultar valores</summary><ul>@foreach($analise['por_avaliacao']??[] as $a)<li><strong>{{ $a['titulo'] }}</strong> · {{ $a['n'] }} resultados · média {{ $numero($a['media_pct']) }} · mediana {{ $numero($a['mediana_pct']) }}</li>@endforeach</ul></details></article>
<article class="card card-pad tu1r3-grafico-card"><h2>Situações registradas</h2><p class="field-help">Critério de cada avaliação · {{ $situacoes['total']??0 }} resultados concluídos.</p><div class="tu1r3-grafico" data-grafico="situacoes" role="img" aria-label="Situações registradas nos resultados"></div><details class="tu1r3-dados"><summary>Consultar valores</summary><ul><li>Dentro do critério: {{ $situacoes['dentro_criterio']??0 }}</li><li>Indicação de recuperação: {{ $situacoes['indicacao_recuperacao']??0 }}</li><li>Outras situações: {{ $situacoes['outros']??0 }}</li></ul></details></article>
<article class="card card-pad tu1r3-grafico-card"><div class="tu1-toolbar"><h2>Acerto por questão</h2><button type="button" class="btn btn-ghost" data-questoes-ordem aria-pressed="false" title="Alternar entre menor acerto e ordem das questões">Ordenar por número</button></div>
@if(count($questoes['opcoes']??[])>1)
<form method="GET" action="{{ route('turmas.show',$turma['id']) }}" data-painel-avaliacao class="tu1r3-avaliacao"><input type="hidden" name="secao" value="visao-geral">@foreach($painelAplicado as $campo=>$valor)@if($campo!=='painel_avaliacao')<input type="hidden" name="{{ $campo }}" value="{{ $valor }}">@endif @endforeach<label for="painel-avaliacao">Avaliação</label><select class="select" id="painel-avaliacao" name="painel_avaliacao">@foreach($questoes['opcoes'] as $a)<option value="{{ $a['id'] }}" @selected(($questoes['avaliacao_id']??null)==$a['id'])>{{ $a['titulo'] }}</option>@endforeach</select><button class="btn btn-secondary" type="submit" aria-label="Atualizar análise da avaliação" title="Atualizar análise da avaliação"><x-icone nome="confirmar" /></button></form>
@else<p class="field-help">{{ $questoes['titulo']??'Nenhuma avaliação disponível' }}</p>@endif
<p class="field-help" data-questoes-legenda>Questões com menor acerto primeiro · base de cada questão exibida no gráfico.</p><div class="tu1r3-grafico tu1r3-questoes" data-grafico="questoes" role="img" aria-label="Percentual de acerto por questão da avaliação selecionada"></div><p class="field-help">A língua segue a opção de cada cartão. Questões anuladas e sem registro são identificadas separadamente.</p><details class="tu1r3-dados"><summary>Consultar valores</summary><ul>@foreach($questoes['items']??[] as $q)<li>Questão {{ $q['numero'] }}: {{ $q['anulada']?'Anulada':$numero($q['pct']) }} · base {{ $q['base'] }} · sem registro {{ $q['sem_registro'] }}</li>@endforeach</ul></details></article>
</div>
@if($acompanhamento)
<div class="card card-pad tu1r3-leituras"><h2>Leituras dos cartões</h2><div class="tu1r3-pendencias">@foreach(['revisao'=>'Precisam de revisão','processamento'=>'Em processamento','falhas'=>'Falhas','nao_identificados'=>'Não identificados','aguardando_leitura'=>'Aguardando leitura'] as $campo=>$rotulo)<span><strong>{{ $acompanhamento[$campo]??0 }}</strong> {{ $rotulo }}</span>@endforeach</div></div>
@endif
@include('turmas._painel_documento')
@endif
@push('scripts')<script src="{{ asset('js/turma-painel.js') }}?v={{ filemtime(public_path('js/turma-painel.js')) }}" defer></script><script src="{{ asset('js/turma-graficos.js') }}?v={{ filemtime(public_path('js/turma-graficos.js')) }}" defer></script>@endpush
