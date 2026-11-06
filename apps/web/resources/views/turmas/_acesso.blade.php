{{-- Seção: Professores vinculados --}}
@unless(\App\Support\Acesso::pessoal())
<div class="card card-pad">
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
    <circle cx="12" cy="7" r="4"/>
  </svg>
  <span id="professores">Acesso à turma</span>
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($professores ?? []) }}</span>
</h2>

<p class="field-help">{{ \App\Support\Acesso::pode('turma.vincular_professor')?'Gerencie o acesso à turma.':'Consulte os acessos à turma.' }}</p>
<details class="tu1-ajuda"><summary>Saiba mais sobre o acesso</summary><p>O acesso permite consultar a turma. A disciplina e o período de atuação são definidos em Disciplinas e professores. Conceder acesso não indica o professor em uma disciplina nem cria uma atribuição docente. Essas ações são independentes e mantêm seu histórico.</p></details>
  {{-- Vincular professor --}}
  @if(!empty($dc1Retorno))@include('disciplinas._voltar',['retorno'=>$dc1Retorno])@endif
  @if($acessoErro??false)<x-aviso tipo="erro">Não foi possível consultar os acessos da turma. Atualize a página antes de fazer alterações.</x-aviso>@else
  @pode('turma.vincular_professor')
  @php
  $candidatos=collect($todosProfessores)->filter(function($p)use($professores,$turma){
      $ligado=isset($p['vinculo_id'])?collect($professores)->contains('vinculo_id',$p['vinculo_id']):collect($professores)->contains('id',$p['id']);
      return !$ligado && (!isset($p['vinculo_id']) || ($p['ativo'] && (int)$p['escola_id']===(int)$turma['escola_id']));
  });
  @endphp
  @if($candidatos->isNotEmpty())
  <form method="POST" action="{{ route('turmas.professores.store', $turma['id']) }}"
        class="tu1-acoes">
    @csrf <x-revisao-acesso />
    <div class="field"><label for="professor-selecionado">Professor para acesso à turma</label>
    <select class="select" name="usuario_id" id="professor-selecionado" required>
      <option value="">— Selecione um professor —</option>
      @foreach($todosProfessores as $p)
        @php $jaVinculado = isset($p['vinculo_id']) ? collect($professores)->contains('vinculo_id', $p['vinculo_id']) : collect($professores)->contains('id', $p['id']); @endphp
        @if(!$jaVinculado && (!isset($p['vinculo_id']) || ($p['ativo'] && (int) $p['escola_id'] === (int) $turma['escola_id'])))
          <option value="{{ $p['id'] }}" data-vinculo="{{ $p['vinculo_id'] ?? '' }}" @selected((string)old('usuario_id')===(string)$p['id'] && (!isset($p['vinculo_id']) || (string)old('vinculo_id')===(string)$p['vinculo_id']))>{{ $p['nome'] }} ({{ $p['email'] }})</option>
        @endif
      @endforeach
    </select></div>
    @if(session('auth_vinculos'))<input type="hidden" name="vinculo_id" id="professor-vinculo">@endif
    <button type="submit" class="btn btn-primary btn-sm">Conceder acesso</button>
  </form>
  @else<p class="field-help">Nenhum professor disponível para receber acesso neste momento. Confira os vínculos ativos na equipe e os acessos já concedidos abaixo.</p>@endif
  @endpode

  @if(count($professores ?? []) > 0)
    <x-tabela :integrada="true" titulo="Professores da turma" modo="lista"><table class="tabela-padrao table " style="margin-top:0;"><caption class="sr-only">Professores da turma</caption>
      <thead>
        <tr>
          <th scope="col">Professor(a)</th>
          <th scope="col">E-mail</th>
          <th scope="col">Status</th>
          <th scope="col">Ações</th>
        </tr>
      </thead>
      <tbody>
        @foreach($professores as $p)
          @php $ini = collect(explode(' ', $p['nome']))->filter()->map(fn($x) => mb_strtoupper(mb_substr($x,0,1)))->take(2)->implode(''); @endphp
          <tr>
            <td class="tabela-principal" data-rotulo="Professor(a)"><div class="tabela-valor">
              <div class="student-name">
                <div class="student-av">{{ $ini }}</div>
                <strong>{{ $p['nome'] }}</strong>
              </div>
            </div></td>
            <td data-rotulo="E-mail"><div class="tabela-valor">{{ $p['email'] }}</div></td>
            <td data-rotulo="Status"><div class="tabela-valor">
              @if($p['ativo'] ?? true)
                <span class="badge badge-success badge-dot">Ativo</span>
              @else
                <span class="badge badge-danger badge-dot">Inativo</span>
              @endif
            </div></td>
            <td style="text-align:right;" class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor">
              @pode('turma.vincular_professor')<form method="POST"
                    action="{{ route('turmas.professores.destroy', array_filter(['id' => $turma['id'], 'uid' => $p['id'], 'vinculo_id' => $p['vinculo_id'] ?? null])) }}"
                    data-turma-confirmar="Retirar o acesso de {{ $p['nome'] }} a esta turma? As atribuições e os dados históricos serão preservados.">
                @csrf <x-revisao-acesso />
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-ghost"
                        style="color:var(--danger);border-color:var(--danger);">Retirar acesso</button>
              </form>@endpode
            </div></td>
          </tr>
        @endforeach
      </tbody>
    </table></x-tabela>
  @else
    <div style="text-align:center;padding:24px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="turma" /></div>
      <div style="font-size:14px;">Nenhum professor com acesso direto a esta turma.</div>
    </div>
  @endif
  @endif
</div>

@endunless

