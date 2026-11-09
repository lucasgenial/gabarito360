@php $painelIndisponivel=(bool)($painelErro??false); @endphp
@if($painel || $painelIndisponivel)
@if($painelIndisponivel)<x-aviso tipo="erro">Não foi possível carregar os indicadores da turma. Atualize a página para tentar novamente.</x-aviso>@endif
@php
$k=($painel['kpis']??[])+['avaliacoes'=>null,'resultados'=>null,'alunos_com_resultado'=>null]; $analise=$painel['analise']??[]; $resumo=$analise['resumo']??[];
$filtroValor=fn($nome)=>is_scalar(request($nome))?(string)request($nome):'';
$painelAplicado=$painelAplicado??[]; $painelErros=$painelErros??[];
$situacoes=$painel['situacoes_resultados']??[]; $acompanhamento=$painel['acompanhamento']??null;
$numero=fn($v)=>$v===null?'—':number_format($v,1,',','').'%';
$indicadores=[
 ['Avaliações aplicadas',$k['avaliacoes'],'prova','azul',($k['avaliacoes_sem_resultado']??0).' sem resultado concluído'],
 ['Resultados concluídos',$k['resultados'],'confirmar','verde','Resultados no recorte selecionado'],
 ['Alunos com resultado',$k['alunos_com_resultado']??0,'turma','violeta','Cadastros distintos de alunos com resultado'],
 ['Acerto médio',$numero($resumo['media_pct']??$k['acerto_pct']??null),'grafico','azul',($resumo['n']??$k['resultados_com_percentual']??0).' resultados na base'],
 ['Mediana',$numero($resumo['mediana_pct']??null),'grafico','turquesa','Ponto central dos percentuais'],
 ['Faixa observada',isset($resumo['min_pct'])?$numero($resumo['min_pct']).' – '.$numero($resumo['max_pct']):'—','grafico','ambar','Do menor ao maior percentual'],
];
if($painelIndisponivel){$indicadores=array_map(fn($i)=>[$i[0],'—',$i[2],$i[3],'Dados temporariamente indisponíveis'],$indicadores);}
$disciplinasPainel=$painel['opcoes']['disciplinas']??[];
$disciplinaUnica=in_array(\App\Support\Acesso::perfil(),['professor','professor_autonomo'],true)&&count($disciplinasPainel)===1&&!isset($painelErros['painel_disciplina']);
$analiseGraficos=array_intersect_key($analise,array_flip(['distribuicao_acertos','por_avaliacao']));
$ajudasIndicadores=\App\Support\IndicadoresTurma::ajudas(is_array($painel)?$painel:[],$painelConsultadoEm??null,$painelIndisponivel);
$vaziosIndicadores=\App\Support\IndicadoresTurma::vazios(is_array($painel)?$painel:[],$painelIndisponivel);
@endphp
<dialog class="tu1-dialog" data-painel-modal data-painel-erros="{{ count($painelErros) }}" aria-labelledby="painel-filtros-titulo">
<div class="tu1-dialog-cabecalho"><h2 id="painel-filtros-titulo">Filtrar indicadores</h2><button class="btn btn-ghost tu1-icone-acao" type="button" data-painel-cancelar aria-label="Fechar filtros" title="Fechar filtros"><x-icone nome="fechar" /></button></div>
<form method="GET" action="{{ route('turmas.show',$turma['id']) }}" data-painel-filtros>
<input type="hidden" name="secao" value="visao-geral">
@foreach($painelAplicado as $campo=>$valor)<input type="hidden" name="painel_anterior[{{ $campo }}]" value="{{ $valor }}">@endforeach
@if($painelErros)<p role="alert" class="tu1-erro">Confira os filtros. Os indicadores abaixo mantêm o recorte anterior.</p>@endif
<div class="form-row-2">
<div class="field">@if($disciplinaUnica)<input type="hidden" name="painel_disciplina" value="{{ $filtroValor('painel_disciplina') }}"><span id="painel-disciplina-rotulo" class="label">Disciplina da avaliação</span><p class="painel-filtro-fixo" aria-labelledby="painel-disciplina-rotulo">{{ $disciplinasPainel[0]['nome'] }}</p>@else<label for="painel-disciplina">Disciplina da avaliação</label><select id="painel-disciplina" class="select" name="painel_disciplina" @if(isset($painelErros['painel_disciplina'])) aria-invalid="true" aria-describedby="erro-painel-disciplina" @endif><option value="">Todas as disciplinas</option>@foreach($disciplinasPainel as $d)<option value="{{ $d['id'] }}" @selected(request('painel_disciplina')==$d['id'])>{{ $d['nome'] }}</option>@endforeach</select>@if(isset($painelErros['painel_disciplina']))<p class="tu1-erro" id="erro-painel-disciplina">{{ $painelErros['painel_disciplina'][0] }}</p>@endif @endif</div>
<div class="field"><label for="painel-professor">Professor autor</label><select id="painel-professor" class="select" name="painel_professor" @if(isset($painelErros['painel_professor'])) aria-invalid="true" aria-describedby="erro-painel-professor" @endif><option value="">Todos os autores autorizados</option>@foreach($painel['opcoes']['professores']??[] as $p)<option value="{{ $p['id'] }}" @selected(request('painel_professor')==$p['id'])>{{ $p['nome'] }}</option>@endforeach</select>@if(isset($painelErros['painel_professor']))<p class="tu1-erro" id="erro-painel-professor">{{ $painelErros['painel_professor'][0] }}</p>@endif</div>
<div class="field"><label for="painel-inicio">Aplicadas a partir de</label><input id="painel-inicio" class="input" type="date" name="painel_inicio" @if(isset($painelErros['painel_inicio'])) aria-invalid="true" aria-describedby="erro-painel-inicio" @endif value="{{ $filtroValor('painel_inicio') }}">@if(isset($painelErros['painel_inicio']))<p class="tu1-erro" id="erro-painel-inicio">{{ $painelErros['painel_inicio'][0] }}</p>@endif</div>
<div class="field"><label for="painel-fim">Aplicadas até</label><input id="painel-fim" class="input" type="date" name="painel_fim" @if(isset($painelErros['painel_fim'])) aria-invalid="true" aria-describedby="erro-painel-fim" @endif value="{{ $filtroValor('painel_fim') }}">@if(isset($painelErros['painel_fim']))<p class="tu1-erro" id="erro-painel-fim">{{ $painelErros['painel_fim'][0] }}</p>@endif</div>
</div>
<div class="painel-valores-acessiveis">O período usa a data de aplicação; avaliações sem data ficam fora quando há um limite de período. Professor autor é quem criou a avaliação atuando como professor, não necessariamente o docente atual. Disciplina seleciona a avaliação inteira: uma avaliação multidisciplinar não tem sua nota dividida por disciplina.</div>
<div class="tu1-acoes"><button class="btn btn-secondary" type="button" data-painel-limpar><x-icone nome="limpar" /> Limpar</button><button class="btn btn-secondary" type="button" data-painel-cancelar><x-icone nome="fechar" /> Cancelar</button><button class="btn btn-primary" type="submit"><x-icone nome="confirmar" /> Aplicar filtros</button></div>
</form></dialog>

<x-painel-secao id="resumo" titulo="Indicadores da turma">
@php($idsIndicadores=['kpi-avaliacoes','kpi-resultados','kpi-alunos-resultado','kpi-media','kpi-mediana','kpi-faixa'])
@foreach($indicadores as [$rotulo,$valor,$icone,$tom,$ajuda])
<x-painel-widget :id="$idsIndicadores[$loop->index]" :ajuda="$ajudasIndicadores[$idsIndicadores[$loop->index]]??[]" :titulo="$rotulo" :w="3" :h="4" secao="resumo" :demo="false" class="painel-widget-kpi tu1-tom-{{ $tom }}">
<div class="painel-kpi-valor"><strong>@if($rotulo==='Faixa observada'&&isset($resumo['min_pct']))<span class="tu1r3-nowrap">{{ $numero($resumo['min_pct']) }}</span> – <span class="tu1r3-nowrap">{{ $numero($resumo['max_pct']) }}</span>@else{{ $valor }}@endif</strong><span aria-hidden="true"><x-icone :nome="$icone" /></span></div>
<span class="painel-valores-acessiveis">{{ $ajuda }}</span>
</x-painel-widget>
@endforeach
</x-painel-secao>
<section class="card card-pad painel-resultados" data-painel-card="resultados" aria-labelledby="titulo-resultados-turma">
<header class="painel-card-cabecalho"><h2 id="titulo-resultados-turma">Resultados da turma</h2><x-painel-mover-grupo tipo="card" titulo="Resultados da turma" /></header>
@include('turmas._painel_alunos')
@include('turmas._modelos',['paginaModelos'=>'turma','gruposExibidos'=>['periodo-agenda'],'nivelGrupos'=>3])
<div class="painel-dados-reais" data-painel-secao-bloco="analises" data-painel-graficos data-painel-dados="{{ json_encode(['analise'=>$analiseGraficos,'situacoes'=>$situacoes],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) }}">
<x-painel-secao id="analises" titulo="Análises da turma" :nivel="3">
<x-painel-widget id="distribuicao-acertos" data-painel-vazio="{{ ($vaziosIndicadores['distribuicao-acertos']??false)?'true':'false' }}" :ajuda="$ajudasIndicadores['distribuicao-acertos']??[]" :nivel="4" titulo="Distribuição dos acertos" :w="6" :h="9" secao="analises" :demo="false" class="painel-widget-grafico">@if($painelIndisponivel)<p class="painel-widget-indisponivel">Dados temporariamente indisponíveis. Atualize a página para tentar novamente.</p>@else<span class="painel-valores-acessiveis">Quantos resultados estão em cada faixa de acerto · base {{ $resumo['n']??0 }}.</span><div class="tu1r3-grafico" data-grafico="distribuicao" role="img" aria-label="Distribuição dos resultados por faixa de acerto"></div><div class="painel-valores-acessiveis"><ul>@foreach($analise['distribuicao_acertos']['faixas']??[] as $f)<li>{{ $f['label'] }}: <strong>{{ $f['total'] }}</strong></li>@endforeach</ul></div>@endif</x-painel-widget>
<x-painel-widget id="resultados-avaliacao" data-painel-vazio="{{ ($vaziosIndicadores['resultados-avaliacao']??false)?'true':'false' }}" :ajuda="$ajudasIndicadores['resultados-avaliacao']??[]" :nivel="4" titulo="Resultados por avaliação" :w="6" :h="9" secao="analises" :demo="false" class="painel-widget-grafico">@if($painelIndisponivel)<p class="painel-widget-indisponivel">Dados temporariamente indisponíveis. Atualize a página para tentar novamente.</p>@else<span class="painel-valores-acessiveis">Média e mediana dos acertos · cada avaliação mantém sua base.</span><div class="tu1r3-grafico" data-grafico="avaliacoes" role="img" aria-label="Média e mediana por avaliação"></div><nav class="painel-grafico-paginas" data-avaliacoes-paginacao aria-label="Avaliações representadas no gráfico" hidden><button class="btn btn-secondary" type="button" data-avaliacoes-anterior title="Mostrar avaliações anteriores"><x-icone nome="voltar" /> Anterior</button><p data-avaliacoes-status role="status" aria-live="polite"></p><button class="btn btn-secondary painel-grafico-proximo" type="button" data-avaliacoes-proxima title="Mostrar próximas avaliações">Próxima <x-icone nome="voltar" /></button></nav><div class="painel-valores-acessiveis"><ul>@foreach($analise['por_avaliacao']??[] as $a)<li><strong>{{ $a['titulo'] }}</strong> · {{ $a['n'] }} resultados · média {{ $numero($a['media_pct']) }} · mediana {{ $numero($a['mediana_pct']) }}</li>@endforeach</ul></div>@endif</x-painel-widget>
<x-painel-widget id="situacoes-registradas" data-painel-vazio="{{ ($vaziosIndicadores['situacoes-registradas']??false)?'true':'false' }}" :ajuda="$ajudasIndicadores['situacoes-registradas']??[]" :nivel="4" titulo="Situações registradas" :w="6" :h="9" secao="analises" :demo="false" class="painel-widget-grafico">@if($painelIndisponivel)<p class="painel-widget-indisponivel">Dados temporariamente indisponíveis. Atualize a página para tentar novamente.</p>@else<span class="painel-valores-acessiveis">Critério de cada avaliação · {{ $situacoes['total']??0 }} resultados concluídos.</span><div class="tu1r3-grafico" data-grafico="situacoes" role="img" aria-label="Situações registradas nos resultados"></div><div class="painel-valores-acessiveis"><ul><li>Dentro do critério: {{ $situacoes['dentro_criterio']??0 }}</li><li>Indicação de recuperação: {{ $situacoes['indicacao_recuperacao']??0 }}</li><li>Outras situações: {{ $situacoes['outros']??0 }}</li></ul></div>@endif</x-painel-widget>
</x-painel-secao>
</div>
</section>
@include('turmas._painel_documento')
@endif
@include('layouts._echarts')
@push('scripts')<script src="@ativo('js/turma-painel.js')" defer></script><script src="@ativo('js/turma-graficos.js')" defer></script>@endpush

@push('styles')<link rel="stylesheet" href="@ativo('css/painel-indicadores.css')">@endpush
