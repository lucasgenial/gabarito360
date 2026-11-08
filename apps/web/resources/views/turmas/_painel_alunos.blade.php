@if(!in_array(\App\Support\Acesso::perfil(),['aluno','aplicador'],true) && \App\Support\Acesso::pode('aluno.ver') && \App\Support\Acesso::pode('resultado.ver'))
@php
$analiseAlunos=$analise['alunos']??[];
$widgetsAlunos=[
 'destaques'=>['id'=>'alunos-destaques','titulo'=>'Destaques da turma','descricao'=>'Até cinco alunos com maiores acertos médios no recorte. Bases diferentes não representam comparação de proficiência. Empates mantêm a mesma posição.'],
 'atencao'=>['id'=>'alunos-atencao','titulo'=>'Alunos que precisam de atenção','descricao'=>'Alunos com ao menos um resultado com indicação de recuperação registrada no recorte.'],
];
@endphp
<x-painel-secao id="estudantes" titulo="Estudantes" :nivel="3">
@foreach($widgetsAlunos as $chave=>$widgetAluno)
<x-painel-widget :id="$widgetAluno['id']" data-painel-vazio="{{ ($vaziosIndicadores[$widgetAluno['id']]??false)?'true':'false' }}" :ajuda="$ajudasIndicadores[$widgetAluno['id']]??[]" :nivel="4" :titulo="$widgetAluno['titulo']" :w="6" :h="12" secao="estudantes" :demo="false" class="painel-widget-alunos">
<div class="tu1r3-alunos" data-painel-alunos="{{ $chave }}" aria-describedby="descricao-{{ $widgetAluno['id'] }}">
<span class="painel-valores-acessiveis" id="descricao-{{ $widgetAluno['id'] }}">{{ $widgetAluno['descricao'] }}</span>
@if($painelIndisponivel??false)<p class="painel-widget-indisponivel">Dados temporariamente indisponíveis. Atualize a página para tentar novamente.</p>
@elseif($analiseAlunos['disponivel']??false)
<ol class="tu1r3-alunos-lista">
@forelse($analiseAlunos[$chave]['items']??[] as $aluno)
<li data-alunos-item @if($loop->index>=5) hidden @endif>
@if($chave==='destaques')<span class="tu1r3-posicao" aria-label="Posição {{ $aluno['posicao'] }}">{{ $aluno['posicao'] }}º</span>@else<span class="tu1r3-aluno-marca" aria-hidden="true">{{ mb_strtoupper(mb_substr($aluno['nome'],0,1)) }}</span>@endif
<div><strong>{{ $aluno['nome'] }}</strong><span class="painel-aluno-base" title="Resultados concluídos e quantidade com percentual válido">{{ $aluno['resultados'] }} resultados · n={{ $aluno['base_valida'] }}@if($chave==='atencao') · {{ $aluno['recuperacao'] }} em recuperação @endif</span></div>
<span class="tu1r3-aluno-media">{{ $numero($aluno['media_pct']) }}</span>
</li>
@empty<li class="tu1r3-alunos-vazio">{{ $chave==='atencao'?'Nenhum aluno com indicação de recuperação neste recorte.':'Ainda não há percentuais válidos neste recorte.' }}</li>@endforelse
</ol>
@if($chave==='atencao'&&($analiseAlunos[$chave]['total']??0)>5)<button type="button" class="btn btn-ghost" data-alunos-expandir aria-expanded="false">Ver todos ({{ $analiseAlunos[$chave]['total'] }})</button>@endif
@if($chave==='destaques'&&($analiseAlunos['destaques']['empate_no_corte']??false))<span class="painel-valores-acessiveis">Há outros alunos empatados na posição final apresentada.</span>@endif
@else<p class="tu1-vazio">O acompanhamento nominal não está disponível neste perfil.</p>@endif
</div>
</x-painel-widget>
@endforeach
</x-painel-secao>
@endif
