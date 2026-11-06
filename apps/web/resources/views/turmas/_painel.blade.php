@if($painelErro??false)<x-aviso tipo="erro">Não foi possível carregar os indicadores da turma. Atualize a página para tentar novamente.</x-aviso>
@elseif($painel)
@php
$k=$painel['kpis'];
$filtroValor=fn($nome)=>is_scalar(request($nome))?(string)request($nome):'';
$situacoes=$painel['situacoes_resultados']??[];
$acompanhamento=$painel['acompanhamento']??null;
$painelAplicado=$painelAplicado??[]; $painelErros=$painelErros??[];
$filtrosAtivos=collect($painelAplicado)->filter(fn($v)=>$v!==null&&$v!=='')->count();
@endphp
<div class="card card-pad tu1-painel-cabecalho">
<div class="tu1-toolbar"><div><h2>Indicadores da turma</h2><p class="field-help">Avaliações aplicadas · resultados autorizados para seu perfil</p></div><button class="btn btn-secondary tu1-icone-acao" type="button" data-painel-abrir aria-label="Filtrar indicadores{{ $filtrosAtivos?' · '.$filtrosAtivos.' filtros ativos':'' }}" title="Filtrar indicadores"><x-icone nome="filtro" />@if($filtrosAtivos)<span class="badge">{{ $filtrosAtivos }}</span>@endif</button></div>
<p class="tu1-recorte"><strong>Dados filtrados:</strong> Disciplina: {{ $painel['recorte']['disciplina'] }} · Professor autor: {{ $painel['recorte']['professor'] }} · Período: {{ !empty($painelAplicado['painel_inicio'])?\Carbon\Carbon::parse($painelAplicado['painel_inicio'])->format('d/m/Y'):'Início não delimitado' }} até {{ !empty($painelAplicado['painel_fim'])?\Carbon\Carbon::parse($painelAplicado['painel_fim'])->format('d/m/Y'):'fim não delimitado' }}.</p>
<details class="dc1-ajuda"><summary>Saiba mais sobre os indicadores</summary><p>O período considera a data de aplicação. Avaliações sem data entram apenas quando o período não está delimitado. Professor autor é quem criou a avaliação atuando como professor. Disciplina seleciona a avaliação inteira, sem dividir questões. O acerto médio usa percentuais de resultados concluídos; não representa diagnóstico de aprendizagem. A quantidade de alunos considera a matrícula atual, enquanto os resultados preservam a turma da aplicação.</p></details>
</div>
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
<div class="tu1-acoes"><button class="btn btn-secondary" type="button" data-painel-limpar><x-icone nome="limpar" /> Limpar</button><button class="btn btn-secondary" type="button" data-painel-cancelar><x-icone nome="fechar" /> Cancelar</button><button class="btn btn-primary" type="submit"><x-icone nome="confirmar" /> Aplicar filtros</button></div>
</form></dialog>
<div class="kpi-grid tu1-indicadores">
@foreach(['alunos'=>['Alunos ativos','turma','neutro'],'avaliacoes'=>['Avaliações aplicadas','prova','azul'],'resultados'=>['Resultados concluídos','confirmar','verde'],'acerto_pct'=>['Acerto médio','grafico','azul']] as $chave=>[$rotulo,$icone,$tom])
<div class="card kpi tu1-indicador tu1-tom-{{ $tom }}"><div class="tu1-kpi-topo"><span class="kpi-label">{{ $rotulo }}</span><x-icone :nome="$icone" /></div><div class="kpi-value">{{ $chave==='acerto_pct'?($k[$chave]===null?'—':number_format($k[$chave],1,',','').'%'):$k[$chave] }}</div><p class="field-help">{{ match($chave){'alunos'=>'Matrícula atual da turma','avaliacoes'=>($k['avaliacoes_sem_resultado']??0).' sem resultado concluído','resultados'=>($k['alunos_com_resultado']??0).' aluno(s) com resultado',default=>($k['resultados_com_percentual']??0).' resultado(s) na base de cálculo'} }}</p></div>
@endforeach
</div>
<div class="tu1-analises">
<div class="card card-pad"><div class="tu1-toolbar"><div><h2>Resultados e critério da avaliação</h2><p class="field-help">Contagem de resultados concluídos no recorte.</p></div><x-icone nome="grafico" /></div>
@php($total=(int)($situacoes['total']??0))
@if($total)
<div class="tu1-distribuicao" aria-hidden="true">@foreach(['dentro_criterio'=>'verde','indicacao_recuperacao'=>'ambar','outros'=>'neutro'] as $campo=>$tom)<span class="tu1-fatia tu1-fatia-{{ $tom }}" style="width:{{ 100*($situacoes[$campo]??0)/$total }}%"></span>@endforeach</div>
<ul class="tu1-lista-indicadores">@foreach(['dentro_criterio'=>['Dentro do critério registrado','verde'],'indicacao_recuperacao'=>['Indicação de recuperação','ambar'],'outros'=>['Outras situações registradas','neutro']] as $campo=>[$rotulo,$tom])<li><span><i class="tu1-ponto tu1-fatia-{{ $tom }}" aria-hidden="true"></i>{{ $rotulo }}</span><strong>{{ $situacoes[$campo]??0 }}</strong></li>@endforeach</ul>
@else<p class="tu1-vazio">Ainda não há resultados concluídos neste recorte.</p>@endif
<details class="dc1-ajuda"><summary>Saiba mais sobre as situações</summary><p>As situações usam o critério já registrado em cada resultado. Provas com critérios diferentes não são convertidas em um único limite. A indicação de recuperação orienta a conferência do professor; não é um diagnóstico automático.</p></details>
</div>
@if($acompanhamento)
<div class="card card-pad"><div class="tu1-toolbar"><div><h2>Pendências de leitura</h2><p class="field-help">Cartões vigentes atribuídos a esta turma.</p></div><x-icone nome="camera" /></div>
<ul class="tu1-lista-indicadores">@foreach(['revisao'=>['Precisam de revisão','ambar'],'processamento'=>['Em processamento','azul'],'falhas'=>['Falhas de processamento','vermelho'],'nao_identificados'=>['Aluno não identificado','ambar'],'aguardando_leitura'=>['Aguardando leitura','neutro']] as $campo=>[$rotulo,$tom])<li><span><i class="tu1-ponto tu1-fatia-{{ $tom }}" aria-hidden="true"></i>{{ $rotulo }}</span><strong>{{ $acompanhamento[$campo]??0 }}</strong></li>@endforeach</ul>
<p class="field-help">Pendências de leitura não são erros ou notas do aluno. Cartões ainda sem turma atribuída não entram nesta contagem.</p>
</div>@endif
</div>
<div class="card card-pad">
<h2>Acerto médio por avaliação</h2><p class="field-help">Percentual dos resultados concluídos; compare também a quantidade de resultados de cada avaliação.</p>
<div class="tu1-grafico" role="list" aria-label="Acerto médio por avaliação">
@forelse($painel['avaliacoes'] as $a)
<div class="tu1-grafico-linha" role="listitem"><div><strong>{{ $a['titulo'] }}</strong><p class="field-help">{{ $a['disciplina'] }} · {{ $a['data_aplicacao']?\Carbon\Carbon::parse($a['data_aplicacao'])->format('d/m/Y'):'Sem data de aplicação' }} · {{ $a['corrigidos'] }} resultado(s)</p></div><div><div class="tu1-grafico-valor">{{ $a['acerto_pct']===null?'Sem resultado':number_format($a['acerto_pct'],1,',','').'% de acerto' }}</div><div class="tu1-grafico-trilho" aria-hidden="true"><span style="width:{{ max(0,min(100,(float)($a['acerto_pct']??0))) }}%"></span></div></div></div>
@empty<p class="tu1-vazio">Nenhuma avaliação aplicada neste recorte.</p>@endforelse
</div>
<form method="POST" action="{{ route('turmas.painelPdf',$turma['id']) }}" target="_blank" class="tu1-documento" data-painel-documento>
@csrf <x-revisao-acesso />
@foreach(['painel_disciplina','painel_professor','painel_inicio','painel_fim'] as $campo)@if(!empty($painelAplicado[$campo]))<input type="hidden" name="{{ $campo }}" value="{{ $painelAplicado[$campo] }}">@endif @endforeach
<div class="field"><label for="painel-orientacao">Disposição das páginas</label><select class="select" id="painel-orientacao" name="orientacao"><option value="retrato">Vertical (retrato)</option><option value="paisagem">Horizontal (paisagem)</option></select></div>
<button class="btn btn-secondary" type="submit" name="baixar" value="0"><x-icone nome="imprimir" /> Imprimir</button><button class="btn btn-primary" type="submit" name="baixar" value="1"><x-icone nome="pdf" /> Gerar PDF</button>
</form><p class="field-help">O documento usa os dados filtrados exibidos no painel.</p>
</div>
@endif
@push('scripts')<script src="{{ asset('js/turma-painel.js') }}?v={{ filemtime(public_path('js/turma-painel.js')) }}" defer></script>@endpush
