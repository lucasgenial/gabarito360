@if(!in_array(\App\Support\Acesso::perfil(),['aluno','aplicador'],true) && \App\Support\Acesso::pode('aluno.ver') && \App\Support\Acesso::pode('resultado.ver'))
@php($analiseAlunos=$analise['alunos']??[])
<x-painel-widget id="alunos-recorte" titulo="Alunos no recorte" :w="6" :h="12" secao="analises" :demo="false" class="painel-widget-alunos">
<div class="tu1r3-alunos" data-painel-alunos>
@if($painelIndisponivel??false)<p class="painel-widget-indisponivel">Dados temporariamente indisponíveis. Atualize a página para tentar novamente.</p>
@elseif($analiseAlunos['disponivel']??false)
<div class="tu1r3-alunos-escolhas" role="group" aria-label="Acompanhamento dos alunos">@foreach(['atencao'=>'Atenção','dentro_criterio'=>'Dentro do critério','destaques'=>'Top 5'] as $chave=>$rotulo)<button type="button" class="btn btn-secondary" data-alunos-visao="{{ $chave }}" aria-pressed="{{ $loop->first?'true':'false' }}">{{ $rotulo }}@if($chave!=='destaques')<span class="badge">{{ $analiseAlunos[$chave]['total']??0 }}</span>@endif</button>@endforeach</div>
@foreach(['atencao'=>['Indicação de acompanhamento','Ao menos um resultado com indicação de recuperação registrada.'],'dentro_criterio'=>['Dentro do critério','Todos os resultados deste aluno no recorte estão registrados como aprovados.'],'destaques'=>['Maiores acertos médios neste recorte','Médias com quantidades diferentes de resultados não são uma comparação de proficiência.']] as $chave=>[$titulo,$descricao])
<section data-alunos-lista="{{ $chave }}" @unless($loop->first) hidden @endunless aria-label="{{ $titulo }}"><h3>{{ $titulo }}</h3><details class="dc1-ajuda"><summary>Saiba mais</summary><p>{{ $descricao }}</p></details>
<ol class="tu1r3-alunos-lista">@forelse($analiseAlunos[$chave]['items']??[] as $aluno)<li data-alunos-item @if($loop->index>=5) hidden @endif>@if($chave==='destaques')<span class="tu1r3-posicao" aria-label="Posição {{ $aluno['posicao'] }}">{{ $aluno['posicao'] }}º</span>@else<span class="tu1r3-aluno-marca" aria-hidden="true">{{ mb_strtoupper(mb_substr($aluno['nome'],0,1)) }}</span>@endif<div><strong>{{ $aluno['nome'] }}</strong><p class="field-help">{{ $aluno['resultados'] }} resultado(s) · {{ $aluno['base_valida'] }} com percentual válido
@if($chave==='atencao') · {{ $aluno['recuperacao'] }} com indicação de recuperação
@endif
</p></div><span class="tu1r3-aluno-media">{{ $numero($aluno['media_pct']) }}</span></li>@empty<li class="tu1r3-alunos-vazio">{{ $chave==='atencao'?'Nenhum aluno com indicação de recuperação neste recorte.':($chave==='dentro_criterio'?'Nenhum aluno com todos os resultados dentro do critério neste recorte.':'Ainda não há percentuais válidos neste recorte.') }}</li>@endforelse</ol>
@if($chave!=='destaques'&&($analiseAlunos[$chave]['total']??0)>5)<button type="button" class="btn btn-ghost" data-alunos-expandir aria-expanded="false">Ver todos ({{ $analiseAlunos[$chave]['total'] }})</button>@endif
@if($chave==='destaques'&&($analiseAlunos['destaques']['empate_no_corte']??false))<p class="field-help">Há outros alunos empatados nesta posição.</p>@endif
</section>@endforeach
@else<p class="tu1-vazio">O acompanhamento nominal não está disponível neste perfil.</p>@endif
</div>
</x-painel-widget>
@endif
