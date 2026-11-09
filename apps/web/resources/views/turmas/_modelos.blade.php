@php
$modelos=\App\Support\ModelosPaineis::para($paginaModelos, \App\Support\Acesso::perfil()??'', \App\Support\Acesso::permissoes());
$gruposModelos=collect($modelos)->groupBy(fn($m)=>($m['grupo']??'planejamento')==='admin'?'administrativo':($m['grupo']??'planejamento'));
$titulosGrupos=['periodo-agenda'=>'Período e agenda','analises'=>'Análises entre turmas','planejamento'=>'Planejamento','ensino'=>'Ensino e acompanhamento','decisoes'=>'Síntese e decisões','administrativo'=>'Organização administrativa'];
@endphp
@foreach(array_intersect_key($titulosGrupos,array_flip($gruposExibidos??array_keys($titulosGrupos))) as $grupo=>$tituloGrupo)
@php $itensGrupo=$gruposModelos->get($grupo,collect()); @endphp
@if($itensGrupo->isNotEmpty() || ($grupo==='analises' && isset($graficoTurnos)))
<x-painel-secao :id="$grupo" :titulo="$tituloGrupo" :nivel="$nivelGrupos??2">
@if($grupo==='analises' && isset($graficoTurnos))
<x-painel-widget id="cadastros-turno" titulo="Turmas por turno" :w="6" :h="9" secao="analises" :demo="false" class="painel-widget-grafico">
<span class="painel-valores-acessiveis">Distribuição cadastral das turmas autorizadas no recorte. Não representa desempenho ou participação em avaliações.</span>
@if($graficoTurnosErro??false)<p role="alert">Não foi possível consultar a distribuição cadastral. Nenhum valor foi substituído por um exemplo.</p>
@elseif(empty($graficoTurnos['categorias']))<p>Nenhuma turma atende ao recorte selecionado.</p>
@else
<div class="grafico" role="img" aria-label="Quantidade de turmas por turno no recorte autorizado" data-grafico="{{ json_encode($graficoTurnos,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) }}"></div>
<div class="painel-valores-acessiveis">
<table class="table painel-demo-valores"><caption>Turmas por turno</caption><thead><tr><th scope="col">Turno</th><th scope="col">Turmas</th></tr></thead><tbody>@foreach($graficoTurnos['categorias']??[] as $i=>$categoria)<tr><th scope="row">{{ $categoria }}</th><td>{{ $graficoTurnos['series'][0]['valores'][$i]??'—' }}</td></tr>@endforeach</tbody></table>
</div>
@endif
</x-painel-widget>
@endif
@foreach($itensGrupo as $modelo)
@php
$d=\App\Support\DemonstracoesPaineis::para($modelo['id']);
$tipoDemo=$d['tipo']??null;
$graficoDemo=$d['grafico']??[];
if($modelo['id']==='comparacao-turmas'){$graficoDemo['horizontal']=true;$graficoDemo['rotulos_curtos']=array_map(fn($nome)=>preg_replace('/^Turma fictícia /u','',$nome),$graficoDemo['categorias']);}
if(isset($d['base']['respostas_possiveis'])){$graficoDemo['bases']=$d['base']['respostas_possiveis'];$graficoDemo['unidade_base']='respostas possíveis';}
elseif(isset($d['base']['aplicacoes_previstas'])){$graficoDemo['bases']=$d['base']['aplicacoes_previstas'];$graficoDemo['unidade_base']='aplicações previstas';}
if(isset($d['base']['acertos'])){$graficoDemo['numeradores']=$d['base']['acertos'];$graficoDemo['rotulo_numerador']='Acertos';}
elseif(isset($d['base']['com_evidencia'])){$graficoDemo['numeradores']=$d['base']['com_evidencia'];$graficoDemo['rotulo_numerador']='Com evidência';}
$alturaDemo=$tipoDemo==='grafico'?9:($tipoDemo==='agenda'?14:($tipoDemo==='progresso'?10:11));
@endphp
<x-painel-widget :nivel="($nivelGrupos??2)+1" :id="$modelo['id']" :titulo="$modelo['titulo']" :w="6" :h="$alturaDemo" :secao="$grupo" :demo="true" class="painel-widget-demonstracao {{ $tipoDemo==='grafico'?'painel-widget-grafico':'' }}">
@if(!$d || !($d['demonstrativo']??false))
<p class="field-help" role="status">Não foi possível apresentar este exemplo ilustrativo.</p>
@else
<span class="painel-valores-acessiveis">{{ $d['descricao'] }}</span>
@if($tipoDemo==='grafico')
<div class="grafico" role="img" aria-label="{{ $d['descricao'] }}" data-grafico="{{ json_encode($graficoDemo,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) }}"></div>
<div class="painel-valores-acessiveis">
<table class="table painel-demo-valores"><caption>Valores demonstrativos</caption><thead><tr><th scope="col">Categoria ilustrativa</th>@foreach($d['grafico']['series'] as $serie)<th scope="col">{{ $serie['nome'] }}</th>@endforeach @if(isset($graficoDemo['bases']))<th scope="col">Base do exemplo</th>@endif</tr></thead><tbody>@foreach($d['grafico']['categorias'] as $i=>$categoria)<tr><th scope="row">{{ $categoria }}</th>@foreach($d['grafico']['series'] as $serie)<td>{{ $serie['valores'][$i] }}{{ ($d['grafico']['formato']??'')==='pct'?'%':'' }}</td>@endforeach @if(isset($graficoDemo['bases']))<td>{{ $graficoDemo['bases'][$i] }} {{ $graficoDemo['unidade_base'] }}</td>@endif</tr>@endforeach</tbody></table>
</div>
@elseif($tipoDemo==='progresso')
<div class="painel-demo-progresso">
<div class="painel-demo-progresso-resumo"><strong>{{ number_format($d['percentual'],1,',','') }}%</strong><span>{{ $d['concluidas'] }} de {{ $d['total'] }} no exemplo</span></div>
<div class="painel-demo-progresso-trilho" role="progressbar" aria-label="Progresso demonstrativo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $d['percentual'] }}" aria-valuetext="{{ $d['concluidas'] }} de {{ $d['total'] }} no exemplo"><span style="width:{{ max(0,min(100,(float)$d['percentual'])) }}%"></span></div>
</div>
@endif
@if(in_array($tipoDemo,['progresso','tarefas'],true) && !empty($d['situacoes']))
<dl class="painel-demo-contagens">@foreach($d['situacoes'] as $situacao=>$total)<div><dt>{{ ucfirst(str_replace('_',' ',$situacao)) }}</dt><dd>{{ $total }}</dd></div>@endforeach</dl>
@endif
@if(in_array($tipoDemo,['agenda','tarefas','registros','progresso'],true))
@if($tipoDemo==='agenda')
<div class="painel-agenda" data-agenda-demo="{{ json_encode($d['itens'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) }}" role="region" aria-label="Agenda demonstrativa: {{ $modelo['titulo'] }}"></div>
<div class="painel-valores-acessiveis" data-agenda-alternativa>
@endif
<ol class="painel-demo-itens painel-demo-itens--{{ $tipoDemo }}">
@foreach($d['itens'] as $item)
<li class="painel-demo-item">
<div class="painel-demo-data"><time datetime="{{ $item['data'] }}">{{ \Carbon\Carbon::parse($item['data'])->format('d/m') }}</time><span>{{ $item['hora'] }}</span></div>
<div class="painel-demo-item-conteudo"><strong>{{ $item['titulo'] }}</strong><span class="painel-demo-item-dado">{{ $item['contexto'] }}</span><span class="painel-demo-item-dado painel-demo-responsavel">{{ $item['responsavel'] }}</span><span class="painel-demo-situacao">{{ $item['situacao'] }}</span></div>
</li>
@endforeach
</ol>
@if($tipoDemo==='agenda')</div>@endif
@endif
@endif
</x-painel-widget>
@endforeach
</x-painel-secao>
@endif
@endforeach

@if(collect($modelos)->contains(fn($m)=>in_array($m['id'],['agenda-pedagogica','proximos-compromissos','agenda-administrativa'],true)))
@once
@push('styles')<link rel="stylesheet" href="@ativo('css/painel-agenda.css')">@endpush
@push('scripts')<script src="@ativo('js/painel-agenda.js')" defer></script>@endpush
@endonce
@endif

@once
@include('layouts._echarts')
@push('scripts')<script src="@ativo('js/graficos.js')" defer></script>@endpush
@endonce
