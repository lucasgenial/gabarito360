<div class="paineis-turmas-colecao" aria-label="Turmas encontradas">
@forelse($turmas as $t)
  <article class="card card-pad painel-turma-registro">
    <div class="painel-turma-identidade"><x-avatar-turma :turma="$t" tamanho="md" /><div><h2>{{ $t['nome'] }}</h2><p class="field-help">{{ $t['serie'] }} · {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['turno']]??$t['turno'] }}</p></div></div>
    @unless(\App\Support\Acesso::pessoal())<p class="painel-turma-escola">{{ $t['escola_nome']??'Escola não informada' }}</p>@endunless
    <dl class="painel-turma-dados"><div><dt>Ano letivo</dt><dd>{{ $t['ano_letivo'] }}</dd></div><div><dt>Cadastros ativos</dt><dd>{{ $t['total_alunos']??'Não informado' }}</dd></div></dl>
    <div class="painel-turma-rodape"><span class="badge {{ $t['ativo']?'badge-success':'badge-info' }}">{{ $t['ativo']?'Em uso':'Arquivada' }}</span><a class="btn btn-secondary" aria-label="Ver turma {{ $t['nome'] }}" href="{{ route('turmas.show',$t['id']) }}"><x-icone nome="turma" /> Ver turma</a></div>
  </article>
@empty
  <p class="field-help">Nenhuma turma encontrada. {{ $haFiltros?'Ajuste os filtros para consultar outro recorte.':'As turmas autorizadas aparecerão aqui.' }}</p>
@endforelse
</div>
