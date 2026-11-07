@php
$modelos=\App\Support\ModelosPaineis::para($paginaModelos, \App\Support\Acesso::perfil()??'', \App\Support\Acesso::permissoes());
$gruposModelos=collect($modelos)->groupBy(fn($m)=>($m['grupo']??'planejamento')==='admin'?'administrativo':($m['grupo']??'planejamento'));
$titulosGrupos=['analises'=>'Análises entre turmas','planejamento'=>'Planejamento','ensino'=>'Ensino e acompanhamento','decisoes'=>'Síntese e decisões','administrativo'=>'Organização administrativa'];
@endphp
@foreach($titulosGrupos as $grupo=>$tituloGrupo)
@php $itensGrupo=$gruposModelos->get($grupo,collect()); @endphp
@if($itensGrupo->isNotEmpty() || ($grupo==='analises' && isset($graficoTurnos)))
<x-painel-secao :id="$grupo" :titulo="$tituloGrupo">
@if($grupo==='analises' && isset($graficoTurnos))
<x-painel-widget id="cadastros-turno" titulo="Turmas por turno" :w="6" :h="9" secao="analises" :demo="false" class="painel-widget-grafico">
<p class="field-help">Distribuição cadastral das turmas autorizadas no recorte. Não representa desempenho ou participação em avaliações.</p>
<x-grafico :dados="$graficoTurnos" descricao="Quantidade de turmas por turno no recorte autorizado">
<table class="table painel-demo-valores"><caption>Turmas por turno</caption><thead><tr><th scope="col">Turno</th><th scope="col">Turmas</th></tr></thead><tbody>@foreach($graficoTurnos['categorias']??[] as $i=>$categoria)<tr><th scope="row">{{ $categoria }}</th><td>{{ $graficoTurnos['series'][0]['valores'][$i]??'—' }}</td></tr>@endforeach</tbody></table>
</x-grafico>
</x-painel-widget>
@endif
@foreach($itensGrupo as $modelo)
@php
$d=\App\Support\DemonstracoesPaineis::para($modelo['id']);
$tipoDemo=$d['tipo']??null;
$alturaDemo=$tipoDemo==='grafico'?9:($tipoDemo==='progresso'?10:11);
@endphp
<x-painel-widget :id="$modelo['id']" :titulo="$modelo['titulo']" :w="6" :h="$alturaDemo" :secao="$grupo" :demo="true" class="painel-widget-demonstracao">
@if(!$d || !($d['demonstrativo']??false))
<p class="field-help" role="status">Não foi possível apresentar este exemplo ilustrativo.</p>
@else
<p class="painel-demo-descricao">{{ $d['descricao'] }}</p>
@if($tipoDemo==='grafico')
<x-grafico :dados="$d['grafico']" :descricao="$d['descricao']">
<table class="table painel-demo-valores"><caption>Valores demonstrativos</caption><thead><tr><th scope="col">Categoria ilustrativa</th>@foreach($d['grafico']['series'] as $serie)<th scope="col">{{ $serie['nome'] }}</th>@endforeach</tr></thead><tbody>@foreach($d['grafico']['categorias'] as $i=>$categoria)<tr><th scope="row">{{ $categoria }}</th>@foreach($d['grafico']['series'] as $serie)<td>{{ $serie['valores'][$i] }}{{ ($d['grafico']['formato']??'')==='pct'?'%':'' }}</td>@endforeach</tr>@endforeach</tbody></table>
</x-grafico>
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
<ol class="painel-demo-itens painel-demo-itens--{{ $tipoDemo }}">
@foreach($d['itens'] as $item)
<li class="painel-demo-item">
<div class="painel-demo-data"><time datetime="{{ $item['data'] }}">{{ \Carbon\Carbon::parse($item['data'])->format('d/m') }}</time><span>{{ $item['hora'] }}</span></div>
<div class="painel-demo-item-conteudo"><strong>{{ $item['titulo'] }}</strong><p>{{ $item['contexto'] }}</p><p class="painel-demo-responsavel">{{ $item['responsavel'] }}</p><span class="painel-demo-situacao">{{ $item['situacao'] }}</span></div>
</li>
@endforeach
</ol>
@endif
@endif
</x-painel-widget>
@endforeach
</x-painel-secao>
@endif
@endforeach
