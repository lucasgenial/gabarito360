<div class="pc-editor" data-curriculo-editor data-pronto="{{ $pronto?'1':'0' }}" data-falha="{{ $falha?'1':'0' }}" aria-busy="false">
  <input type="hidden" name="curriculo_versao" value="{{ $f['curriculo_versao'] }}">
  @if($conflito)
  <x-aviso tipo="erro"><strong>A configuração foi alterada.</strong> Seu preenchimento foi preservado. Atualize o estado e revise antes de confirmar.
    <div class="pc-acoes"><button type="submit" class="btn btn-secondary" name="acao" value="rever" data-curriculo-intencao formnovalidate formaction="{{ $p?route('provas.curriculo.preparar',$p['id']):route('provas.preparar') }}">Atualizar estado e revisar</button></div>
  </x-aviso>
  @endif
  @if($f['revisao_pendente']??false)
  <section class="card card-pad pc-secao" aria-labelledby="pc-revisao">
    <h2 id="pc-revisao" tabindex="-1">Confira as alterações</h2>
    <p>O estado atual está abaixo. Sua seleção continua nos campos de turmas e disciplinas.</p>
    <dl class="pc-atual"><div><dt>Turmas atuais</dt><dd>{{ collect($p['turmas']??[])->pluck('nome')->join(', ') ?: 'Nenhuma' }}</dd></div><div><dt>Disciplinas atuais</dt><dd>{{ collect($p['curriculo']['disciplinas']??[])->pluck('nome')->join(', ') ?: 'Nenhuma' }}</dd></div></dl>
    <input type="hidden" name="revisao_pendente" value="1">
    <label class="pc-check"><input type="checkbox" name="conferido" value="1" required @checked($f['conferido']??false)>Conferi o estado atual e quero salvar minha seleção abaixo.</label>
    @include('provas.curriculo._erro',['campo'=>'conferido'])
  </section>
  @endif
  @if($falha)<x-aviso tipo="erro">Não foi possível conferir as opções. Sua seleção foi preservada. Use Atualizar opções para tentar novamente.</x-aviso>@endif
  <section class="card card-pad pc-secao" aria-labelledby="pc-turmas-titulo">
    <h2 id="pc-turmas-titulo">Turmas</h2>
    @unless($p)
      @if(count($escolas)===1)<p class="pc-escola"><strong>Escola:</strong> {{ $escolas[0]['nome'] }}</p><input type="hidden" name="escola_id" value="{{ $escolas[0]['id'] }}">
      @else<div class="field"><label for="pc-escola">Escola *</label><select class="select" name="escola_id" id="pc-escola" required><option value="">Selecione</option>@foreach($escolas as $e)<option value="{{ $e['id'] }}" @selected(($f['escola_id']??'')==$e['id'])>{{ $e['nome'] }}</option>@endforeach</select></div>@endif
    @endunless
    <p class="field-help" id="pc-turmas-ajuda">Selecione ao menos uma turma para a aplicação.</p>
    @include('provas.curriculo._erro',['campo'=>'turma_ids'])
    <x-tabela titulo="Turmas da prova" modo="compacta"><table class="tabela-padrao tabela-compacta"><caption class="sr-only">Turmas da prova</caption><thead><tr><th scope="col">Turma</th><th scope="col">Selecionar</th></tr></thead><tbody>
      @forelse($turmas as $t)<tr><td><label for="pc-turma-{{ $t['id'] }}"><strong>{{ $t['nome'] }}</strong><span class="pc-secundario">{{ $t['serie']??'' }} · {{ $t['ano_letivo']??'' }}{{ !empty($t['turno'])?' · '.(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['turno']]??$t['turno']):'' }}</span></label></td><td class="pc-selecao"><label class="pc-alvo"><input id="pc-turma-{{ $t['id'] }}" type="checkbox" name="turma_ids[]" value="{{ $t['id'] }}" @checked(in_array((int)$t['id'],$f['turma_ids'],true)) aria-describedby="pc-turmas-ajuda" aria-label="Selecionar turma {{ $t['nome'] }}" data-curriculo-turma></label></td></tr>
      @empty<tr><td colspan="2">{{ $falha?'A lista de turmas não pôde ser carregada.':'Nenhuma turma ativa disponível neste vínculo.' }}</td></tr>@endforelse
      @foreach($desconhecidas as $id)<tr><td><strong>Turma indisponível</strong><span class="pc-secundario">Desmarque para retirar da seleção.</span></td><td class="pc-selecao"><input type="checkbox" name="turma_ids[]" value="{{ $id }}" checked aria-label="Retirar turma indisponível {{ $loop->iteration }}" data-curriculo-turma></td></tr>@endforeach
    </tbody></table></x-tabela>
  </section>
  <section class="card card-pad pc-secao" aria-labelledby="pc-disciplinas-titulo">
    <h2 id="pc-disciplinas-titulo">Disciplinas da prova</h2>
    <div class="pc-ajuda"><p class="field-help">Cada disciplina deve estar disponível em todas as turmas selecionadas.</p>
    <x-orientacao assunto="disciplinas da prova"><p>O professor precisa estar indicado na disciplina e ter atribuição vigente em todas as turmas selecionadas, além do acesso às turmas. A coordenação organiza esses vínculos. Adicionar ou remover aqui só prepara a seleção; a gravação ocorre no botão final.</p></x-orientacao></div>
    <div class="pc-acoes">
      <div class="field pc-seletor"><label for="pc-nova-disciplina">Selecionar disciplina</label><select class="select" name="nova_disciplina_id" id="pc-nova-disciplina" @include('provas.curriculo._atributos-erro',['campo'=>'nova_disciplina_id']) @disabled($falha || !$f['turma_ids'])><option value="">Selecione uma disciplina</option>@foreach($o['opcoes'] as $d)@unless(in_array((int)$d['id'],$f['disciplina_ids'],true))<option value="{{ $d['id'] }}">{{ $d['nome'] }}{{ !empty($d['sigla'])?' ('.$d['sigla'].')':'' }}</option>@endunless @endforeach</select>@include('provas.curriculo._erro',['campo'=>'nova_disciplina_id'])</div>
      <button type="submit" class="btn btn-secondary" name="acao" value="adicionar" data-curriculo-intencao formnovalidate formaction="{{ $p?route('provas.curriculo.preparar',$p['id']):route('provas.preparar') }}" @disabled($falha || !$f['turma_ids'])>Adicionar à prova</button>
      <button type="submit" class="btn btn-ghost" name="acao" value="opcoes" data-curriculo-intencao formnovalidate formaction="{{ $p?route('provas.curriculo.preparar',$p['id']):route('provas.preparar') }}">Atualizar opções</button>
    </div>
    <p class="pc-feedback" data-curriculo-status role="status" aria-live="polite">@if($falha)Não foi possível conferir as opções. Use Atualizar opções para tentar novamente.@elseif(!$f['turma_ids'])Selecione as turmas para consultar as disciplinas.@elseif(!$o['opcoes'])Nenhuma disciplina comum às turmas. Confira os componentes e as atribuições com a coordenação.@endif</p>
    @include('provas.curriculo._erro',['campo'=>'disciplina_ids'])
    @foreach([true,false] as $elegivel)
      @php($linhas=collect($o['selecionadas'])->filter(fn($d)=>(bool)$d['elegivel']===$elegivel))
      @if($linhas->isNotEmpty() || $elegivel)
      <h3>{{ $elegivel?'Selecionadas para a prova':'Precisam de revisão' }}</h3>
      <x-tabela :titulo="$elegivel?'Disciplinas selecionadas':'Disciplinas que precisam de revisão'" modo="compacta"><table class="tabela-padrao tabela-compacta"><caption class="sr-only">{{ $elegivel?'Disciplinas selecionadas':'Disciplinas que precisam de revisão' }}</caption><thead><tr><th scope="col">Nome/dados</th><th class="tabela-coluna-acoes" scope="col">Ações</th></tr></thead><tbody>
        @forelse($linhas as $d)<tr><td><strong>{{ $d['nome'] }}</strong>@unless($elegivel)<span class="pc-secundario pc-revisar">{{ $d['motivo'] }}</span>@endunless<input type="hidden" name="disciplina_ids[]" value="{{ $d['id'] }}"></td><td class="tabela-acoes"><button type="submit" class="btn btn-ghost btn-sm" name="acao" value="remover:{{ $d['id'] }}" data-curriculo-intencao formnovalidate formaction="{{ $p?route('provas.curriculo.preparar',$p['id']):route('provas.preparar') }}">Remover<span class="sr-only"> {{ $d['nome'] }}</span></button></td></tr>@empty<tr><td colspan="2">Nenhuma disciplina adicionada. Selecione uma disciplina e use Adicionar à prova.</td></tr>@endforelse
      </tbody></table></x-tabela>
      @endif
    @endforeach
    <p class="field-help">{{ count($f['disciplina_ids'])?count($f['disciplina_ids']).(count($f['disciplina_ids'])===1?' disciplina selecionada.':' disciplinas selecionadas.'):'Adicione ao menos uma disciplina para continuar.' }}</p>
  </section>
</div>
