<x-painel-secao id="resumo" titulo="Resumo cadastral do recorte">
@foreach(['turmas'=>['kpi-turmas','Turmas no recorte'],'em_uso'=>['kpi-em-uso','Em uso'],'arquivadas'=>['kpi-arquivadas','Arquivadas'],'cadastros_ativos'=>['kpi-cadastros','Cadastros de alunos ativos']] as $campo=>[$id,$rotulo])
<x-painel-widget :id="$id" :titulo="$rotulo" secao="resumo" :w="3" :h="4"><div class="painel-kpi-valor"><strong aria-label="{{ ($resumoCadastral[$campo]??null)===null?'Indisponível':$resumoCadastral[$campo] }}">{{ $resumoCadastral[$campo]??'—' }}</strong></div></x-painel-widget>
@endforeach
</x-painel-secao>
@if(in_array($resumoCadastral['estado'],['erro','parcial'],true))<p role="status">Há totais indisponíveis neste recorte. Eles estão indicados por um traço.</p>@endif
