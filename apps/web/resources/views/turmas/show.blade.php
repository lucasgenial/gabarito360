@extends('layouts.app')

@section('tela', 'tela-turma')

@section('title', $turma['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
<span>{{ $turma['nome'] }}</span>
@endsection

@section('content')

<x-pagina titulo="{{ $turma['nome'] }}">
  <x-slot:sub>{{ $turma['serie'] }} ·
      {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$turma['turno']] ?? $turma['turno'] }} ·
      <b>{{ $turma['total_alunos'] ?? 0 }}</b> aluno(s)
      @if(!empty($turma['escola_nome'])) · {{ $turma['escola_nome'] }}@endif</x-slot:sub>
    @pode('correcao.acompanhar')<a class="btn btn-primary" href="{{ route('correcao.turma', $turma['id']) }}"><x-icone nome="grafico" /> Acompanhar correção</a>@endpode
    @pode('resultado.ver')<a class="btn btn-secondary" href="{{ route('relatorios.turma', $turma['id']) }}"><x-icone nome="grafico" /> Relatório da turma</a>@endpode
    @pode('turma.gerir')
    <button class="btn btn-secondary" onclick="abrirModal()">Editar turma</button>
    <form method="POST" action="{{ route('turmas.toggle', $turma['id']) }}"
          onsubmit="return confirm('{{ $turma['ativo'] ? 'Desativar' : 'Ativar' }} esta turma?')">
      @csrf
      <button type="submit" class="btn {{ $turma['ativo'] ? 'btn-ghost' : 'btn-secondary' }}"
              style="color:{{ $turma['ativo'] ? 'var(--danger)' : 'var(--success)' }};{{ $turma['ativo'] ? 'border-color:var(--danger);' : '' }}">
        {{ $turma['ativo'] ? 'Desativar' : 'Ativar' }}
      </button>
    </form>
    @endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Desempenho médio</div>
    <div class="kpi-value">{{ $turma['media_turma'] !== null ? number_format($turma['media_turma'], 1, ',', '') : '—' }}</div>
    <div class="kpi-trend">média da turma</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas aplicadas</div>
    <div class="kpi-value">{{ $turma['total_provas'] ?? 0 }}</div>
    <div class="kpi-trend">nesta turma</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos ativos</div>
    <div class="kpi-value">{{ $turma['total_alunos'] ?? 0 }}</div>
    <div class="kpi-trend">matriculados</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Ano letivo</div>
    <div class="kpi-value">{{ $turma['ano_letivo'] }}</div>
    <div class="kpi-trend">{{ $turma['ativo'] ? 'Ativa' : 'Inativa' }}</div>
  </div>
</div>

{{-- Seção de alunos --}}
<h2 class="section-title secao-topo">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
  </svg>
  Alunos da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['alunos'] ?? []) }}</span>
</h2>

<div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input id="aluno-search" class="input" type="search" placeholder="Buscar aluno por nome…"
           style="padding-left:36px;" oninput="filtrarAlunos(this.value)" />
  </div>
  <a href="{{ route('alunos.create', ['turma_id' => $turma['id']]) }}" class="btn btn-primary btn-sm">+ Adicionar aluno</a>
</div>

<div class="card" style="overflow:hidden;">
  <x-tabela titulo="Alunos da turma" modo="lista"><table class="tabela-padrao table " id="alunos-table"><caption class="sr-only">Alunos da turma</caption>
    <thead>
      <tr>
        <th scope="col">Aluno</th>
        <th scope="col">Matrícula</th>
        <th scope="col">Média geral</th>
        <th scope="col">Provas</th>
        <th scope="col">Status</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody id="alunos-tbody">
      @forelse($turma['alunos'] ?? [] as $a)
        <tr data-nome="{{ strtolower($a['nome']) }}">
          <td class="tabela-principal" data-rotulo="Aluno"><div class="tabela-valor">
            <div class="student-name">
              <x-avatar :avatar-id="$a['avatar_id'] ?? null" :nome="$a['nome']" :aluno-id="$a['id']" :tem-foto="$a['tem_foto']??false" :imagem-versao="$a['imagem_versao']??1" tamanho="sm" />
              <strong>{{ $a['nome'] }}</strong>
            </div>
          </div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Matrícula"><div class="tabela-valor">{{ $a['matricula'] ?? '—' }}</div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Média geral"><div class="tabela-valor">{{ ($a['media_geral'] ?? null) !== null ? number_format($a['media_geral'], 1, ',', '') : '—' }}</div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Provas"><div class="tabela-valor">{{ $a['total_provas'] ?? 0 }}</div></td>
          <td data-rotulo="Status"><div class="tabela-valor">
            @if($a['ativo'] ?? true)
              <span class="badge badge-success badge-dot">Ativo</span>
            @else
              <span class="badge badge-danger badge-dot">Inativo</span>
            @endif
          </div></td>
          <td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary">Ver</a></div></td>
        </tr>
      @empty
        <tr id="empty-row">
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="aluno" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno matriculado</div>
            <p>Os alunos aparecerão aqui após o cadastro no módulo de Alunos.</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table></x-tabela>
  <div id="no-results" style="display:none;padding:32px;text-align:center;color:var(--muted);font-size:14px;">
    Nenhum aluno corresponde à busca.
  </div>
</div>

{{-- Seção: Provas da turma --}}
<h2 class="section-title secao-topo">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
    <path d="M14 2v6h6"/>
  </svg>
  Provas da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['provas'] ?? []) }}</span>
</h2>

<div class="card" style="overflow:hidden;margin-bottom:24px;">
  @if(count($turma['provas'] ?? []) > 0)
  <x-tabela titulo="Provas da turma" modo="lista"><table class="tabela-padrao table "><caption class="sr-only">Provas da turma</caption>
    <thead>
      <tr>
        <th scope="col">Prova</th>
        <th scope="col">Disciplina</th>
        <th scope="col">Aplicação</th>
        <th scope="col">Média</th>
        <th scope="col">Status</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      @php
        $statusCfg = [
          'rascunho'    => ['cls'=>'badge-info',   'label'=>'Rascunho'],
          'publicada'   => ['cls'=>'badge-success', 'label'=>'Publicada'],
          'em_correcao' => ['cls'=>'badge-warn', 'label'=>'Em correção', 'style'=>''],
          'corrigida'   => ['cls'=>'badge-success', 'label'=>'Corrigida'],
        ];
      @endphp
      @foreach($turma['provas'] as $p)
        @php $sc = $statusCfg[$p['status']] ?? ['cls'=>'badge-info','label'=>$p['status']]; @endphp
        <tr>
          <td class="tabela-principal" data-rotulo="Prova"><div class="tabela-valor"><strong>{{ $p['titulo'] }}</strong></div></td>
          <td data-rotulo="Disciplina"><div class="tabela-valor">{{ $p['disciplina'] }}</div></td>
          <td data-rotulo="Aplicação"><div class="tabela-valor">{{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}</div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Média"><div class="tabela-valor">{{ $p['media'] !== null ? number_format($p['media'], 1, ',', '') : '—' }}</div></td>
          <td data-rotulo="Status"><div class="tabela-valor"><span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span></div></td>
          <td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor">
            @if($p['status'] === 'em_correcao')
              <a href="{{ route('correcao.show', $p['id']) }}" class="btn btn-sm btn-secondary">Acompanhar</a>
            @elseif($p['status'] === 'corrigida')
              <a href="{{ route('relatorios.turmaProva', [$turma['id'], $p['id']]) }}" class="btn btn-sm btn-secondary">Relatório</a>
            @else
              <a href="{{ route('provas.show', $p['id']) }}" class="btn btn-sm btn-secondary">Ver</a>
            @endif
          </div></td>
        </tr>
      @endforeach
    </tbody>
  </table></x-tabela>
  @else
  <div style="text-align:center;padding:48px 20px;color:var(--muted);">
    <div class="icone-grande"><x-icone nome="prova" /></div>
    <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova aplicada</div>
    <p>As provas vinculadas a esta turma aparecerão aqui.</p>
  </div>
  @endif
</div>

{{-- Seção: Professores vinculados --}}
@unless(\App\Support\Acesso::pessoal())
<h2 class="section-title secao-topo">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
    <circle cx="12" cy="7" r="4"/>
  </svg>
  Professores
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($professores ?? []) }}</span>
</h2>

<div class="card card-pad" style="margin-bottom:24px;">
  {{-- Vincular professor --}}
  <form method="POST" action="{{ route('turmas.professores.store', $turma['id']) }}"
        style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    @csrf
    <select class="select" name="usuario_id" id="professor-selecionado" required style="flex:1;min-width:200px;">
      <option value="">— Selecione um professor —</option>
      @foreach($todosProfessores as $p)
        @php $jaVinculado = isset($p['vinculo_id']) ? collect($professores)->contains('vinculo_id', $p['vinculo_id']) : collect($professores)->contains('id', $p['id']); @endphp
        @if(!$jaVinculado && (!isset($p['vinculo_id']) || ($p['ativo'] && (int) $p['escola_id'] === (int) $turma['escola_id'])))
          <option value="{{ $p['id'] }}" data-vinculo="{{ $p['vinculo_id'] ?? '' }}">{{ $p['nome'] }} ({{ $p['email'] }})</option>
        @endif
      @endforeach
    </select>
    @if(session('auth_vinculos'))<input type="hidden" name="vinculo_id" id="professor-vinculo">@endif
    <button type="submit" class="btn btn-primary btn-sm">Vincular professor</button>
  </form>

  @if(count($professores ?? []) > 0)
    <x-tabela titulo="Professores da turma" modo="lista"><table class="tabela-padrao table " style="margin-top:0;"><caption class="sr-only">Professores da turma</caption>
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
              <form method="POST"
                    action="{{ route('turmas.professores.destroy', array_filter(['id' => $turma['id'], 'uid' => $p['id'], 'vinculo_id' => $p['vinculo_id'] ?? null])) }}"
                    onsubmit="return confirm('Desvincular {{ $p['nome'] }} desta turma?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-ghost"
                        style="color:var(--danger);border-color:var(--danger);">Desvincular</button>
              </form>
            </div></td>
          </tr>
        @endforeach
      </tbody>
    </table></x-tabela>
  @else
    <div style="text-align:center;padding:24px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="turma" /></div>
      <div style="font-size:14px;">Nenhum professor vinculado a esta turma ainda.</div>
    </div>
  @endif
</div>

@endunless

{{-- Modal de edição --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar turma</h2>
      <button type="button" class="modal-close" onclick="fecharModal()">&times;</button>
    </div>
    <form method="POST" action="{{ route('turmas.update', $turma['id']) }}">
      @csrf
      @method('PUT')
      <div class="modal-body">
        <div class="form-row-2">
          <div class="field" style="grid-column:1/-1">
            <label for="ed-nome">Nome da turma *</label>
            <input class="input" type="text" id="ed-nome" name="nome" required value="{{ $turma['nome'] }}" />
          </div>
          <div class="field">
            <label for="ed-serie">Série *</label>
            <input class="input" type="text" id="ed-serie" name="serie" required value="{{ $turma['serie'] }}" />
          </div>
          <div class="field">
            <label for="ed-turno">Turno *</label>
            <select class="select" id="ed-turno" name="turno" required>
              @foreach(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'] as $v => $l)
                <option value="{{ $v }}" {{ $turma['turno'] === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          @unless(\App\Support\Acesso::pessoal())
<div class="field">
            <label for="ed-escola">Escola</label>
            <select class="select" id="ed-escola" name="escola_id">
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}" {{ ($turma['escola_id'] ?? '') == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
@endunless
          <div class="field">
            <label for="ed-ano">Ano letivo</label>
            <input class="input num" type="number" id="ed-ano" name="ano_letivo"
                   value="{{ $turma['ano_letivo'] }}" min="2020" max="2099" />
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
var modal = document.getElementById('modal-editar');

function abrirModal() {
  modal.classList.add('open');
  modal.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function fecharModal() {
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
}
modal.addEventListener('click', function(e){ if(e.target===modal) fecharModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') fecharModal(); });

function filtrarAlunos(q) {
  var termo = q.toLowerCase().trim();
  var rows  = document.querySelectorAll('#alunos-tbody tr[data-nome]');
  var visiveis = 0;
  rows.forEach(function(row) {
    var show = !termo || row.dataset.nome.includes(termo);
    row.style.display = show ? '' : 'none';
    if(show) visiveis++;
  });
  document.getElementById('no-results').style.display = (visiveis === 0 && termo) ? 'block' : 'none';
}
</script>
@endpush

@push('scripts')
<script>
const professorEscolhido = document.getElementById('professor-selecionado'), professorVinculo = document.getElementById('professor-vinculo');
if (professorEscolhido && professorVinculo) {
 const atualizarVinculo = () => professorVinculo.value = professorEscolhido.selectedOptions[0]?.dataset.vinculo || '';
 professorEscolhido.addEventListener('change', atualizarVinculo); atualizarVinculo();
}
</script>
@endpush
