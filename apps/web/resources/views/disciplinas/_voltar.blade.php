@if(!empty($retorno['retorno_turma']) && !empty($retorno['retorno_componente']))
<a class="btn btn-secondary" href="{{ route('disciplinas.componente', ['turma'=>$retorno['retorno_turma'],'componente'=>$retorno['retorno_componente']] + $retorno) }}">Voltar à atribuição</a>
@elseif(!empty($retorno['retorno_turma']))
<a class="btn btn-secondary" href="{{ route('turmas.show', ['id'=>$retorno['retorno_turma'],'secao'=>'disciplinas']) }}">Voltar à turma</a>
@endif
