@if($painelErro??false)<x-aviso tipo="erro">Não foi possível carregar os indicadores da turma. Atualize a página para tentar novamente.</x-aviso>
@elseif($painel)
<div class="card card-pad">
<h2>Indicadores da turma</h2>
<form method="GET" action="{{ route('turmas.show',$turma['id']) }}" class="tu1-filtros-painel" data-painel-filtros>
<input type="hidden" name="secao" value="visao-geral">
<div class="form-row-2">
<div class="field"><label for="painel-disciplina">Disciplina da avaliação</label><select id="painel-disciplina" class="select" name="painel_disciplina"><option value="">Todas as disciplinas</option>@foreach($painel['opcoes']['disciplinas'] as $d)<option value="{{ $d['id'] }}" @selected(request('painel_disciplina')==$d['id'])>{{ $d['nome'] }}</option>@endforeach</select></div>
<div class="field"><label for="painel-professor">Professor autor</label><select id="painel-professor" class="select" name="painel_professor"><option value="">Todos os autores autorizados</option>@foreach($painel['opcoes']['professores'] as $p)<option value="{{ $p['id'] }}" @selected(request('painel_professor')==$p['id'])>{{ $p['nome'] }}</option>@endforeach</select></div>
<div class="field"><label for="painel-inicio">Aplicadas a partir de</label><input id="painel-inicio" class="input" type="date" name="painel_inicio" value="{{ request('painel_inicio') }}"></div>
<div class="field"><label for="painel-fim">Aplicadas até</label><input id="painel-fim" class="input" type="date" name="painel_fim" value="{{ request('painel_fim') }}"></div>
</div>
<div class="tu1-acoes"><button class="btn btn-primary" type="submit">Aplicar filtros</button><a class="btn btn-secondary" href="{{ route('turmas.show',['id'=>$turma['id'],'secao'=>'visao-geral']) }}">Limpar</a></div>
</form>
<details class="dc1-ajuda"><summary>Saiba mais sobre os indicadores</summary><p>O período considera a data de aplicação. Avaliações em correção ou corrigidas sem data aparecem apenas quando o período não foi delimitado. Só entram avaliações já aplicadas e autorizadas para seu perfil. Professor autor é quem criou a avaliação atuando como professor; avaliações institucionais continuam no recorte geral. O filtro de disciplina seleciona as avaliações vinculadas a ela, sem dividir o resultado da prova em áreas. O acerto médio usa os percentuais de cada resultado válido, para comparar provas com quantidades diferentes de questões.</p></details>
</div>
<div class="kpi-grid">
@foreach(['alunos'=>'Alunos ativos','avaliacoes'=>'Avaliações aplicadas','resultados'=>'Resultados concluídos','acerto_pct'=>'Acerto médio'] as $chave=>$rotulo)
<div class="card kpi"><div class="kpi-label">{{ $rotulo }}</div><div class="kpi-value">{{ $chave==='acerto_pct'?($painel['kpis'][$chave]===null?'—':number_format($painel['kpis'][$chave],1,',','').'%'):$painel['kpis'][$chave] }}</div></div>
@endforeach
</div>
<div class="card card-pad">
<h2>Desempenho por avaliação</h2>
<p class="field-help">{{ $painel['recorte']['disciplina'] }} · {{ $painel['recorte']['professor'] }}</p>
<div class="tu1-grafico" role="list" aria-label="Acerto médio por avaliação">
@forelse($painel['avaliacoes'] as $a)
<div class="tu1-grafico-linha" role="listitem"><div><strong>{{ $a['titulo'] }}</strong><p class="field-help">{{ $a['disciplina'] }} · {{ $a['data_aplicacao']?\Carbon\Carbon::parse($a['data_aplicacao'])->format('d/m/Y'):'Sem data de aplicação' }} · {{ $a['corrigidos'] }} resultado(s)</p></div>
<div><div class="tu1-grafico-valor">{{ $a['acerto_pct']===null?'Sem resultado':number_format($a['acerto_pct'],1,',','').'% de acerto' }}</div><div class="tu1-grafico-trilho" aria-hidden="true"><span style="width:{{ max(0,min(100,(float)($a['acerto_pct']??0))) }}%"></span></div></div></div>
@empty<p>Nenhuma avaliação aplicada neste recorte.</p>@endforelse
</div>
<p class="field-help" role="status" data-painel-pendente hidden>Aplique os filtros alterados antes de gerar ou imprimir o documento.</p>
<form method="POST" action="{{ route('turmas.painelPdf',$turma['id']) }}" target="_blank" class="tu1-documento" data-painel-documento>
@csrf <x-revisao-acesso />
@foreach(['painel_disciplina','painel_professor','painel_inicio','painel_fim'] as $campo)@if(request()->filled($campo))<input type="hidden" name="{{ $campo }}" value="{{ request($campo) }}">@endif @endforeach
<div class="field"><label for="painel-orientacao">Disposição das páginas</label><select class="select" id="painel-orientacao" name="orientacao"><option value="retrato">Vertical (retrato)</option><option value="paisagem">Horizontal (paisagem)</option></select></div>
<button class="btn btn-secondary" type="submit" name="baixar" value="0"><x-icone nome="imprimir" /> Imprimir</button><button class="btn btn-primary" type="submit" name="baixar" value="1"><x-icone nome="prova" /> Gerar PDF</button>
</form>
<p class="field-help">O documento usa os filtros aplicados acima. Para imprimir, abra o comando de impressão no leitor de PDF.</p>
</div>
@endif
@push('scripts')<script src="{{ asset('js/turma-painel.js') }}" defer></script>@endpush
