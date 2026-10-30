@extends('layouts.app')

@section('tela', 'tela-escola')

@section('title', $escola['nome'])

{{-- Hero (fora do layout padrão de breadcrumb/content) --}}
@section('breadcrumb')@endsection

@section('content')
{{-- Hero --}}
<div class="escola-hero">
  <div>
    <div class="hero-breadcrumb">
      <a href="{{ route('escolas.index') }}">Escolas</a>
      <span style="opacity:.5;">/</span>
      <span>{{ $escola['nome'] }}</span>
    </div>
    <div class="escola-hero-inner">
      <div class="escola-hero-icon"><x-icone nome="escola" /></div>
      <div class="escola-hero-info">
        <h1>{{ $escola['nome'] }}</h1>
        <div class="escola-hero-meta">
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            INEP: <strong>{{ $escola['inep'] }}</strong>
          </span>
          @if($escola['logradouro'] ?? null)
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            {{ $escola['logradouro'] }}@if($escola['cidade'] ?? null), {{ $escola['cidade'] }} / {{ $escola['uf'] ?? '' }}@endif
          </span>
          @endif
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            <strong>{{ $escola['ativo'] ? 'Ativa' : 'Inativa' }}</strong>
          </span>
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            {{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$escola['tipo_rede']] ?? $escola['tipo_rede'] }}
            @if($escola['nucleo_nome'] ?? null) · {{ $escola['nucleo_nome'] }}@endif
          </span>
        </div>
        <div class="hero-actions">
          <a href="{{ route('escolas.index') }}" class="btn-hero btn-hero-solid">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Voltar
          </a>
          <button class="btn-hero" onclick="document.getElementById('tab-btn-turmas').click()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            Ver turmas
          </button>
          <button class="btn-hero" onclick="document.getElementById('tab-btn-equipe').click()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Equipe
          </button>
        </div>
      </div>
    </div>

    <div class="tabs-row" role="tablist" style="margin-top:20px;">
      <button class="tab-btn active" role="tab" id="tab-btn-visao" data-tab="visao-geral">Visão Geral</button>
      <button class="tab-btn" role="tab" id="tab-btn-turmas" data-tab="turmas">Turmas</button>
      <button class="tab-btn" role="tab" id="tab-btn-provas" data-tab="provas">Provas</button>
      <button class="tab-btn" role="tab" id="tab-btn-alunos" data-tab="alunos">Alunos</button>
      <button class="tab-btn" role="tab" id="tab-btn-equipe" data-tab="equipe">Equipe</button>
    </div>
  </div>
</div>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- ═══ TAB: Visão Geral ═══════════════════════════════════════ --}}
<div class="tab-panel active" id="tab-visao-geral">
  <div class="kpi-strip">
    <div class="kpi-card">
      <div class="kpi-label">Alunos ativos</div>
      <div class="kpi-value">{{ $escola['total_alunos'] ?? 0 }}</div>
      <div class="kpi-delta neutral">matrículas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Turmas</div>
      <div class="kpi-value">{{ $escola['total_turmas'] ?? 0 }}</div>
      <div class="kpi-delta neutral">cadastradas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Provas aplicadas</div>
      <div class="kpi-value">{{ $escola['total_provas'] ?? 0 }}</div>
      <div class="kpi-delta neutral">no sistema</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Tipo de rede</div>
      <div class="kpi-value" style="font-size:16px;margin-top:8px;">
        {{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$escola['tipo_rede']] ?? $escola['tipo_rede'] }}
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Status</div>
      <div style="margin-top:10px;">
        @if($escola['ativo'])
          <span class="badge badge-success" style="font-size:14px;padding:6px 14px;">Ativa</span>
        @else
          <span class="badge badge-danger" style="font-size:14px;padding:6px 14px;">Inativa</span>
        @endif
      </div>
    </div>
  </div>

  <div class="detail-grid">
    {{-- Coluna principal: dados da escola --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
      {{-- Logo e relatório da escola (cabeçalho dos relatórios impressos) --}}
      <div class="info-card">
        <h3>Logo e relatório</h3>
        <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:8px;">
          <img src="{{ route('escolas.logo', $escola['id']) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
               style="width:72px;height:72px;object-fit:contain;border:1px solid var(--border-soft);border-radius:8px;background:#fff;">
          <div style="display:none;width:72px;height:72px;place-items:center;border:1px dashed var(--border);border-radius:8px;font-size:11px;color:var(--muted);text-align:center;">sem logo</div>
          <div style="flex:1;min-width:200px;">
            @pode('escola.logo')
            <form method="POST" action="{{ route('escolas.logo.enviar', $escola['id']) }}" enctype="multipart/form-data" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
              @csrf
              <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="input" style="max-width:260px;">
              <button type="submit" class="btn btn-secondary btn-sm">Enviar logo</button>
            </form>
            <p style="font-size:12px;color:var(--muted);margin-top:6px;">PNG ou JPG até 2 MB, de preferência quadrado. Aparece no topo dos relatórios impressos e PDFs.</p>
            @endpode
            @pode('rede.logo')
            @if(!empty($escola['rede_id']))
            <form method="POST" action="{{ route('redes.logo.enviar', $escola['rede_id']) }}" enctype="multipart/form-data" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px;">
              @csrf
              <span style="font-size:13px;font-weight:600;">Logo do ente{{ !empty($escola['rede_nome']) ? ' (' . $escola['rede_nome'] . ')' : '' }}:</span>
              <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="input" style="max-width:240px;">
              <button type="submit" class="btn btn-secondary btn-sm">Enviar logo do ente</button>
            </form>
            <p style="font-size:12px;color:var(--muted);margin-top:4px;">Logo da rede ou da mantenedora. Vale para todas as escolas da rede.</p>
            @endif
            @endpode
            @pode('resultado.ver')
            <a class="btn btn-primary btn-sm" style="margin-top:6px;" href="{{ route('relatorios.escola', $escola['id']) }}"><x-icone nome="grafico" /> Relatório da escola</a>
            @endpode
          </div>
        </div>
      </div>
      <div class="info-card">
        <h3>Dados da escola</h3>
        @php
          $endereco = collect([trim(($escola['logradouro'] ?? '') . (!empty($escola['numero']) ? ', ' . $escola['numero'] : '')),
                               $escola['complemento'] ?? null, $escola['bairro'] ?? null])->filter()->implode(' · ');
          $rotOferta = fn ($campo) => collect($escola[$campo] ?? [])->map(fn ($k) => $opcoes[$campo][$k] ?? $k)->implode(', ');
          $linhas = [
            'Nome'         => $escola['nome'],
            'INEP'         => $escola['inep'] === 'PENDENTE' ? 'Pendente — informe em "Editar dados da escola"' : $escola['inep'],
            'Vínculo'      => ['sede' => 'Sede', 'anexo' => 'Anexo', 'unidade_vinculada' => 'Unidade vinculada'][$escola['tipo'] ?? 'sede'] ?? null,
            'Esfera'       => ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$escola['tipo_rede']] ?? $escola['tipo_rede'],
            'Núcleo'       => ($escola['nucleo_nome'] ?? null) . (!empty($escola['orgao_regional']) ? ' (NTE ' . $escola['orgao_regional'] . ')' : ''),
            'Situação'     => ['ativa' => 'Em atividade', 'paralisada' => 'Paralisada', 'extinta' => 'Extinta'][$escola['situacao_funcionamento'] ?? ''] ?? null,
            'Localização'  => ['urbana' => 'Urbana', 'rural' => 'Rural'][$escola['localizacao'] ?? ''] ?? null,
            'Endereço'     => $endereco ?: null,
            'Município'    => !empty($escola['cidade']) ? $escola['cidade'] . ' / ' . ($escola['uf'] ?? '') . (!empty($escola['co_municipio']) ? ' · IBGE ' . $escola['co_municipio'] : '') : null,
            'CEP'          => !empty($escola['cep']) ? substr($escola['cep'], 0, 5) . '-' . substr($escola['cep'], 5) : null,
            'Telefone'     => $escola['telefone'] ?? null,
            'E-mail'       => $escola['email'] ?? null,
            'Turnos'       => $rotOferta('turnos') ?: null,
            'Etapas'       => $rotOferta('etapas') ?: null,
            'Modalidades'  => $rotOferta('modalidades') ?: null,
            'Origem'       => ($escola['origem'] ?? 'manual') === 'censo' ? 'Censo Escolar ' . ($escola['ano_censo'] ?? '') . (!empty($escola['edicao_manual']) ? ' (editada à mão)' : '') : 'Cadastro manual',
          ];
        @endphp
        @foreach($linhas as $rot => $v)
          @continue($v === null || $v === '')
          <div class="info-row">
            <span class="lbl">{{ $rot }}</span>
            <span class="val" @if($rot === 'INEP') style="font-family:var(--font-mono);" @endif>{{ $v }}</span>
          </div>
        @endforeach
        @if(!empty($escola['sede']))
        <div class="info-row">
          <span class="lbl">Escola sede</span>
          <span class="val"><a href="{{ route('escolas.show', $escola['sede']['id']) }}">{{ $escola['sede']['nome'] }}</a></span>
        </div>
        @endif
        <div class="info-row">
          <span class="lbl">Status</span>
          <span class="val">
            @if($escola['ativo'])
              <span class="badge badge-success">Ativa</span>
            @else
              <span class="badge badge-danger">Inativa</span>
            @endif
          </span>
        </div>
        @pode('escola.editar')
          <a class="btn btn-secondary btn-sm" style="margin-top:12px;" href="{{ route('escolas.edit', $escola['id']) }}">Editar dados da escola</a>
        @endpode
      </div>

      {{-- Anexos (escolas ligadas a esta sede), como no ECOAR --}}
      @if(($escola['tipo'] ?? 'sede') === 'sede' && (count($escola['anexos'] ?? []) || \App\Support\Acesso::pode('escola.criar')))
      <div class="info-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
          <h3 style="margin:0;">Anexos ({{ count($escola['anexos'] ?? []) }})</h3>
          @pode('escola.criar')
            <a class="btn btn-sm btn-primary" href="{{ route('escolas.create', ['sede' => $escola['id']]) }}">+ Cadastrar anexo</a>
          @endpode
        </div>
        @forelse($escola['anexos'] ?? [] as $a)
          <div style="padding:10px 0;border-bottom:1px solid var(--border-soft);overflow-wrap:anywhere;">
            <a href="{{ route('escolas.show', $a['id']) }}" style="font-weight:600;">{{ $a['nome'] }}</a>
            <div style="font-size:13px;color:var(--muted);">INEP {{ $a['inep'] }}{{ !empty($a['cidade']) ? ' · ' . $a['cidade'] : '' }}{{ $a['ativo'] ? '' : ' · inativo' }}</div>
          </div>
        @empty
          <p class="field-help">Nenhum anexo cadastrado para esta escola.</p>
        @endforelse
      </div>
      @endif

      {{-- Turmas resumo --}}
      @if(count($escola['turmas'] ?? []) > 0)
      <div class="info-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <h3 style="margin:0;">Turmas cadastradas</h3>
          <button class="btn btn-sm btn-secondary" onclick="document.getElementById('tab-btn-turmas').click()">Ver todas</button>
        </div>
        <table class="data-table lista-cel">
          <thead>
            <tr>
              <th>Turma</th>
              <th>Turno</th>
              <th>Alunos</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach(array_slice($escola['turmas'], 0, 5) as $t)
            <tr>
              <td><strong>{{ $t['nome'] }}</strong> <span style="color:var(--muted);font-size:12px;">{{ $t['ano'] ?? '' }}</span></td>
              <td>{{ ucfirst($t['turno'] ?? '—') }}</td>
              <td style="font-family:var(--font-mono);">{{ $t['total_alunos'] ?? 0 }}</td>
              <td>
                @if($t['ativo'] ?? true)
                  <span class="badge badge-success">Ativa</span>
                @else
                  <span class="badge badge-danger">Inativa</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        @if(count($escola['turmas']) > 5)
          <div style="padding:10px 0 0;font-size:13px;color:var(--muted);">
            Exibindo 5 de {{ count($escola['turmas']) }} turmas.
            <button class="btn btn-sm btn-ghost" style="padding:2px 10px;" onclick="document.getElementById('tab-btn-turmas').click()">Ver todas</button>
          </div>
        @endif
      </div>
      @endif
    </div>

    {{-- Coluna lateral: equipe resumo --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
      @if(count($escola['equipe'] ?? []) > 0)
      <div class="info-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <h3 style="margin:0;">Equipe</h3>
          <button class="btn btn-sm btn-secondary" onclick="document.getElementById('tab-btn-equipe').click()">Ver todos</button>
        </div>
        @foreach(array_slice($escola['equipe'], 0, 4) as $m)
          @php
            $ini = collect(explode(' ', $m['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
            $labels = ['admin_rede'=>'Admin Rede','dir_nucleo'=>'Dir. Núcleo','dir_escolar'=>'Dir. Escolar','coordenador'=>'Coordenador','professor'=>'Professor','aluno'=>'Aluno'];
          @endphp
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-soft);">
            <div class="team-avatar" style="width:36px;height:36px;font-size:13px;">{{ $ini }}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:600;line-height:1.2;">{{ $m['nome'] }}</div>
              <div style="font-size:12px;color:var(--muted);">{{ $labels[$m['perfil']] ?? $m['perfil'] }}</div>
            </div>
            @if(!($m['ativo'] ?? true))
              <span class="badge badge-danger" style="font-size:10px;">Inativo</span>
            @endif
          </div>
        @endforeach
        @if(count($escola['equipe']) > 4)
          <div style="padding-top:10px;font-size:13px;color:var(--muted);">
            +{{ count($escola['equipe']) - 4 }} membro(s).
            <button class="btn btn-sm btn-ghost" style="padding:2px 10px;" onclick="document.getElementById('tab-btn-equipe').click()">Ver todos</button>
          </div>
        @endif
      </div>
      @else
      <div class="info-card" style="text-align:center;padding:32px 20px;">
        <div class="icone-grande"><x-icone nome="equipe" /></div>
        <div style="font-size:14px;color:var(--muted);">Nenhum membro vinculado a esta escola.</div>
        <a href="{{ route('membros.create') }}" class="btn btn-sm btn-primary" style="margin-top:14px;">Adicionar membro</a>
      </div>
      @endif

      <div class="info-card" style="text-align:center;padding:24px;">
        <div class="icone-grande"><x-icone nome="ajustes" /></div>
        <div style="font-size:13px;font-weight:600;margin-bottom:6px;">Ações rápidas</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
          <a href="{{ route('escolas.index') }}" class="btn btn-secondary btn-sm">← Voltar para escolas</a>
          @pode('escola.editar')
          <a href="{{ route('escolas.edit', $escola['id']) }}" class="btn btn-ghost btn-sm">Editar dados da escola</a>
          @endpode
          @pode('escola.desativar')
          <form method="POST" action="{{ route('escolas.toggle', $escola['id']) }}"
                onsubmit="return confirm('{{ $escola['ativo'] ? 'Desativar' : 'Ativar' }} esta escola?')">
            @csrf
            <button type="submit" class="btn btn-sm"
                    style="width:100%;color:{{ $escola['ativo'] ? 'var(--danger)' : 'var(--success)' }};border-color:{{ $escola['ativo'] ? 'var(--danger)' : 'var(--success)' }};background:transparent;">
              {{ $escola['ativo'] ? 'Desativar escola' : 'Ativar escola' }}
            </button>
          </form>
          @endpode
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══ TAB: Turmas ════════════════════════════════════════════ --}}
<div class="tab-panel" id="tab-turmas">
  <div class="info-card" style="margin-top:4px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="margin:0;">Turmas cadastradas</h3>
      <a href="{{ route('turmas.index') }}" class="btn btn-sm btn-primary">+ Nova turma</a>
    </div>
    @if(count($escola['turmas'] ?? []) > 0)
    <table class="data-table lista-cel">
      <thead>
        <tr>
          <th>Turma</th>
          <th>Ano</th>
          <th>Turno</th>
          <th>Alunos</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($escola['turmas'] as $t)
        <tr>
          <td><strong>{{ $t['nome'] }}</strong></td>
          <td>{{ $t['ano'] ?? '—' }}</td>
          <td>{{ ucfirst($t['turno'] ?? '—') }}</td>
          <td style="font-family:var(--font-mono);">{{ $t['total_alunos'] ?? 0 }}</td>
          <td>
            @if($t['ativo'] ?? true)
              <span class="badge badge-success">Ativa</span>
            @else
              <span class="badge badge-danger">Inativa</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div style="text-align:center;padding:48px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="escola" /></div>
      <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma turma cadastrada</div>
      <p>As turmas desta escola aparecerão aqui após o cadastro no módulo de Turmas.</p>
    </div>
    @endif
  </div>
</div>

{{-- ═══ TAB: Provas ════════════════════════════════════════════ --}}
<div class="tab-panel" id="tab-provas">
  <div class="info-card" style="margin-top:4px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="margin:0;">Provas desta escola</h3>
      <a href="{{ route('provas.create') }}" class="btn btn-sm btn-primary">+ Nova prova</a>
    </div>
    @if(count($escola['provas'] ?? []) > 0)
    <table class="data-table lista-cel">
      <thead>
        <tr>
          <th>Prova</th>
          <th>Disciplina</th>
          <th>Turma</th>
          <th>Aplicação</th>
          <th>Alunos</th>
          <th>Média</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($escola['provas'] as $p)
          @php
            $statusCfg = [
              'rascunho'    => ['cls'=>'badge-info',   'label'=>'Rascunho'],
              'publicada'   => ['cls'=>'badge-success', 'label'=>'Publicada'],
              'em_correcao' => ['cls'=>'badge-warn', 'label'=>'Em correção', 'style'=>''],
              'corrigida'   => ['cls'=>'badge-success', 'label'=>'Corrigida'],
            ];
            $sc = $statusCfg[$p['status']] ?? ['cls'=>'badge-info','label'=>$p['status']];
          @endphp
          <tr>
            <td><strong>{{ $p['titulo'] }}</strong></td>
            <td>{{ $p['disciplina'] }}</td>
            <td>{{ $p['turma'] }}</td>
            <td>{{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}</td>
            <td style="font-family:var(--font-mono);">{{ $p['total_alunos'] }}</td>
            <td style="font-family:var(--font-mono);">{{ $p['media'] !== null ? number_format($p['media'], 1, ',', '') : '—' }}</td>
            <td><span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span></td>
            <td>
              @if($p['status'] === 'em_correcao')
                <a href="{{ route('correcao.show', $p['id']) }}" class="btn btn-sm btn-secondary">Acompanhar</a>
              @elseif($p['status'] === 'corrigida')
                <a href="{{ route('relatorios.prova', $p['id']) }}" class="btn btn-sm btn-secondary">Relatório</a>
              @else
                <a href="{{ route('provas.show', $p['id']) }}" class="btn btn-sm btn-secondary">Ver</a>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div style="text-align:center;padding:48px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="prova" /></div>
      <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova cadastrada</div>
      <p>As provas desta escola aparecerão aqui após a criação no módulo de Provas.</p>
    </div>
    @endif
  </div>
</div>

{{-- ═══ TAB: Alunos ════════════════════════════════════════════ --}}
<div class="tab-panel" id="tab-alunos">
  <div class="info-card" style="margin-top:4px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="margin:0;">Alunos matriculados <span class="badge" style="margin-left:8px;">{{ count($escola['alunos'] ?? []) }}</span></h3>
      <a href="{{ route('alunos.create') }}" class="btn btn-sm btn-primary">+ Cadastrar</a>
    </div>
    @if(count($escola['alunos'] ?? []) > 0)
    <table class="data-table lista-cel">
      <thead>
        <tr>
          <th>Aluno</th>
          <th>Matrícula</th>
          <th>Turma</th>
          <th>Média geral</th>
          <th>Provas</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($escola['alunos'] as $a)
          <tr>
            <td><strong>{{ $a['nome'] }}</strong></td>
            <td style="font-family:var(--font-mono);">{{ $a['matricula'] }}</td>
            <td>{{ $a['turma'] }}</td>
            <td style="font-family:var(--font-mono);">{{ $a['media_geral'] !== null ? number_format($a['media_geral'], 1, ',', '') : '—' }}</td>
            <td style="font-family:var(--font-mono);">{{ $a['total_provas'] }}</td>
            <td>
              @if($a['ativo'])
                <span class="badge badge-success">Ativa</span>
              @else
                <span class="badge badge-danger">Inativa</span>
              @endif
            </td>
            <td><a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary">Ver</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div style="text-align:center;padding:48px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="aluno" /></div>
      <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno matriculado</div>
      <p>Os alunos desta escola aparecerão aqui após o cadastro nas turmas.</p>
    </div>
    @endif
  </div>
</div>

{{-- ═══ TAB: Equipe ════════════════════════════════════════════ --}}
<div class="tab-panel" id="tab-equipe">
  <div style="display:flex;align-items:center;justify-content:space-between;margin:4px 0 18px;">
    <h3 style="font-size:16px;font-weight:700;">Equipe pedagógica</h3>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('membros.index') }}" class="btn btn-sm btn-secondary">Gerenciar membros</a>
      <a href="{{ route('membros.create') }}" class="btn btn-sm btn-primary">+ Adicionar membro</a>
    </div>
  </div>

  @php
    $perfisOrdem = ['admin_rede','dir_escolar','coordenador','professor','aluno'];
    $labels = ['admin_rede'=>'Admin Rede','dir_nucleo'=>'Dir. Núcleo','dir_escolar'=>'Direção Escolar','coordenador'=>'Coordenação','professor'=>'Professores','aluno'=>'Alunos'];
    $equipeAgrupada = collect($escola['equipe'] ?? [])->groupBy('perfil');
  @endphp

  @if($equipeAgrupada->isEmpty())
    <div class="info-card" style="text-align:center;padding:60px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="equipe" /></div>
      <h3 style="font-size:18px;font-weight:700;color:var(--fg-2);margin-bottom:8px;">Equipe vazia</h3>
      <p>Nenhum membro vinculado a esta escola ainda.</p>
      <a href="{{ route('membros.create') }}" class="btn btn-primary" style="margin-top:16px;">Adicionar primeiro membro</a>
    </div>
  @else
    @foreach($perfisOrdem as $perfil)
      @if($equipeAgrupada->has($perfil))
        <div style="margin-bottom:24px;">
          <div class="team-section-label">{{ $labels[$perfil] ?? $perfil }}</div>
          <div class="team-grid">
            @foreach($equipeAgrupada[$perfil] as $m)
              @php
                $ini = collect(explode(' ', $m['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
              @endphp
              <div class="team-card">
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="team-avatar">{{ $ini }}</div>
                  <div>
                    <div class="team-name">{{ $m['nome'] }}</div>
                    <div class="team-role">{{ $m['email'] }}</div>
                    @if(!($m['ativo'] ?? true))
                      <span class="badge badge-danger" style="font-size:10px;margin-top:4px;">Inativo</span>
                    @endif
                  </div>
                </div>
                <a href="{{ route('membros.edit', $m['id']) }}" class="btn btn-sm btn-secondary">Editar</a>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    @endforeach
  @endif
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.tab-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

</script>
@endpush
