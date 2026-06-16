@extends('layouts.app')

@section('title', 'Painel')

@php
    $activityIcon = function (string $tipo): array {
        return match (true) {
            str_starts_with($tipo, 'resultado') => ['✓', 'var(--success-light)', 'var(--success)'],
            str_starts_with($tipo, 'agenda') => ['🗓', 'var(--accent-light)', 'var(--accent-dark)'],
            str_contains($tipo, 'gabarito') => ['↑', 'var(--accent-light)', 'var(--accent-dark)'],
            str_contains($tipo, 'pendencia') || str_contains($tipo, 'ambig') => ['!', 'var(--warn-light)', 'var(--warn-fg)'],
            default => ['•', 'var(--accent-light)', 'var(--accent-dark)'],
        };
    };
@endphp

@section('content')
    @php
        $navRoutes = collect($portalUi['navigation'] ?? [])->pluck('route');
        $canExams = $navRoutes->contains('portal.exams.index');
        $canClasses = $navRoutes->contains('portal.classes.index');
        $canSchools = $navRoutes->contains('portal.schools.index');
        $canOperations = $navRoutes->contains('portal.operations.index');
    @endphp

    <span class="sr-only">Painel Gabarito360</span>

    @if ($dashboardVariant === 'admin')
        @php($adminKpis = $adminDashboard['kpis'])
        <div class="breadcrumb"><span>Início</span><span class="sep">/</span><span>Painel administrativo</span></div>

        <div class="row-between" style="margin-top:12px;">
            <div>
                <div class="eyebrow">Visão da rede</div>
                <h1 class="page-title">Painel administrativo</h1>
                <p class="page-sub">Gestão global de escolas, usuários, provas e operação OMR.</p>
            </div>
            <div class="dashboard-action-row">
                @if ($canSchools)
                    <a href="{{ route('portal.schools.index') }}" class="btn btn-secondary">Gerir escolas</a>
                @endif
                @if ($canExams)
                    <a href="{{ route('portal.exams.create') }}" class="btn btn-primary">Nova prova</a>
                @endif
            </div>
        </div>

        <div class="kpi-grid">
            <div class="card kpi">
                <div class="kpi-label">Escolas no escopo</div>
                <div class="kpi-value">{{ number_format($adminKpis['schools'], 0, ',', '.') }}</div>
                <div class="kpi-trend">rede autorizada</div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Turmas ativas</div>
                <div class="kpi-value">{{ number_format($adminKpis['classes'], 0, ',', '.') }}</div>
                <div class="kpi-trend">com alunos vinculados</div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Alunos cadastrados</div>
                <div class="kpi-value">{{ number_format($adminKpis['students'], 0, ',', '.') }}</div>
                <div class="kpi-trend">dados sensíveis protegidos</div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Usuários</div>
                <div class="kpi-value">{{ number_format($adminKpis['users'], 0, ',', '.') }}</div>
                <div class="kpi-trend">perfis e lotações</div>
            </div>
        </div>

        <div class="main-grid">
            <div class="card card-pad">
                <div class="chart-head">
                    <h3>Indicadores administrativos</h3>
                    <span class="badge badge-info">RBAC ativo</span>
                </div>
                <div class="admin-metric-grid">
                    <div>
                        <span>Média geral</span>
                        <strong>{{ $adminKpis['average'] !== null ? number_format($adminKpis['average'], 1, ',', '.') : '-' }}</strong>
                        <small>escala 0-10</small>
                    </div>
                    <div>
                        <span>Aplicações</span>
                        <strong>{{ number_format($adminKpis['applications'], 0, ',', '.') }}</strong>
                        <small>no escopo autorizado</small>
                    </div>
                    <div>
                        <span>Pendências OMR</span>
                        <strong>{{ number_format($adminKpis['pending_readings'], 0, ',', '.') }}</strong>
                        <small>leituras para revisão</small>
                    </div>
                </div>
                <ul class="alert-list">
                    @foreach ($adminDashboard['alerts'] as $alert)
                        <li class="alert-item alert-{{ $alert['tone'] }}">
                            <div>
                                <strong>{{ $alert['title'] }}</strong>
                                <span>{{ $alert['meta'] }}</span>
                            </div>
                            <span class="badge badge-{{ $alert['tone'] === 'info' ? 'info' : ($alert['tone'] === 'warn' ? 'warn' : ($alert['tone'] === 'success' ? 'success' : 'muted')) }}">{{ ucfirst($alert['tone']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card card-pad">
                <h3 style="font-size:17px;margin-bottom:12px;">Usuários recentes</h3>
                @if ($adminDashboard['recent_users']->isNotEmpty())
                    <ul class="data-list">
                        @foreach ($adminDashboard['recent_users'] as $account)
                            @php($profileName = $account->perfilVinculos->first()?->perfil?->nome ?? 'Sem perfil ativo')
                            <li>
                                <div>
                                    <strong>{{ $account->nome }}</strong>
                                    <span>{{ $profileName }}</span>
                                </div>
                                <small>{{ $account->ultimo_acesso_at?->diffForHumans() ?? 'sem acesso recente' }}</small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="state-panel state-panel-compact">Nenhum usuário cadastrado ainda.</div>
                @endif
            </div>
        </div>

        <h3 style="font-size:17px;margin-top:32px;">Ações administrativas</h3>
        <div class="quick-actions">
            @if ($canSchools)
                <a class="qa" href="{{ route('portal.schools.index') }}"><div class="qa-ico">ESC</div><b>Organizar escolas</b><span>Núcleos, escolas, status e cobertura da rede</span></a>
            @endif
            @if ($canExams)
                <a class="qa" href="{{ route('portal.exams.index') }}"><div class="qa-ico">PRV</div><b>Gerir provas</b><span>Banco de provas, gabaritos e aplicações</span></a>
            @endif
            @if ($canOperations)
                <a class="qa" href="{{ route('portal.operations.index') }}"><div class="qa-ico">OMR</div><b>Monitorar correções</b><span>Leituras, revisões e resultados</span></a>
            @endif
        </div>
        <div style="height:48px"></div>
    @elseif ($dashboardVariant === 'applicator')
        @php($operatorKpis = $applicatorDashboard['kpis'])
        <div class="breadcrumb"><span>Início</span><span class="sep">/</span><span>Painel do aplicador</span></div>

        <div class="row-between" style="margin-top:12px;">
            <div>
                <div class="eyebrow">Operação vinculada</div>
                <h1 class="page-title">Painel do aplicador</h1>
                <p class="page-sub">Acompanhe somente suas turmas, aplicações e leituras autorizadas.</p>
            </div>
            @if ($canOperations)
                <a href="{{ route('portal.operations.index') }}" class="btn btn-primary">Abrir correções</a>
            @endif
        </div>

        <div class="kpi-grid">
            <div class="card kpi">
                <div class="kpi-label">Turmas vinculadas</div>
                <div class="kpi-value">{{ number_format($operatorKpis['linked_classes'], 0, ',', '.') }}</div>
                <div class="kpi-trend">acesso operacional</div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Aplicações</div>
                <div class="kpi-value">{{ number_format($operatorKpis['applications'], 0, ',', '.') }}</div>
                <div class="kpi-trend">sob sua responsabilidade</div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Leituras confirmadas</div>
                <div class="kpi-value">{{ number_format($operatorKpis['confirmed_readings'], 0, ',', '.') }}</div>
                <div class="kpi-trend">cartões processados</div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Pendências</div>
                <div class="kpi-value" @style(['color:var(--warn-fg)' => $operatorKpis['pending_readings'] > 0])>{{ number_format($operatorKpis['pending_readings'], 0, ',', '.') }}</div>
                <div class="kpi-trend" @style(['color:var(--warn-fg)' => $operatorKpis['pending_readings'] > 0])>
                    {{ $operatorKpis['pending_readings'] > 0 ? 'aguardando revisão' : 'fila limpa' }}
                </div>
            </div>
        </div>

        <div class="main-grid">
            <div class="card card-pad">
                <div class="chart-head">
                    <h3>Minhas aplicações</h3>
                    <span class="badge badge-info">{{ $operatorKpis['ongoing'] }} em andamento</span>
                </div>
                @if ($applicatorDashboard['applications']->isNotEmpty())
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Aplicação</th>
                                    <th>Turma</th>
                                    <th>Status</th>
                                    <th>Leituras</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($applicatorDashboard['applications'] as $application)
                                    <tr>
                                        <td>
                                            <strong>{{ $application->titulo }}</strong>
                                            <div class="act-time">{{ $application->inicio_previsto_at?->format('d/m/Y H:i') ?? 'Sem data prevista' }}</div>
                                        </td>
                                        <td>{{ $application->turma?->nome ?? 'Turma não informada' }}</td>
                                        <td><span class="badge badge-muted">{{ str_replace('_', ' ', $application->status) }}</span></td>
                                        <td class="num">{{ $application->leituras_count }}/{{ $application->alunos_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="state-panel state-panel-compact">Nenhuma aplicação vinculada ao seu usuário.</div>
                @endif
            </div>

            <div class="card card-pad">
                <h3 style="font-size:17px;margin-bottom:12px;">Turmas de atuação</h3>
                @if ($applicatorDashboard['classes']->isNotEmpty())
                    <ul class="data-list">
                        @foreach ($applicatorDashboard['classes'] as $class)
                            <li>
                                <div>
                                    <strong>{{ $class->nome }}</strong>
                                    <span>{{ $class->escola?->nome ?? 'Escola não informada' }}</span>
                                </div>
                                <small>{{ $class->matriculas_count }} alunos</small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="state-panel state-panel-compact">Nenhuma turma vinculada ao aplicador.</div>
                @endif
            </div>
        </div>

        <h3 style="font-size:17px;margin-top:32px;">Ações do aplicador</h3>
        <div class="quick-actions">
            @if ($canOperations)
                <a class="qa" href="{{ route('portal.operations.index') }}"><div class="qa-ico">OMR</div><b>Acompanhar correções</b><span>Confirmar leituras e revisar pendências</span></a>
            @endif
            @if ($canClasses)
                <a class="qa" href="{{ route('portal.classes.index') }}"><div class="qa-ico">TRM</div><b>Ver minhas turmas</b><span>Consultar alunos do escopo operacional</span></a>
            @endif
        </div>
        <div style="height:48px"></div>
    @else
    <div class="breadcrumb"><span>Início</span><span class="sep">/</span><span>Painel</span></div>

    <div class="row-between" style="margin-top:12px;">
        <div>
            <h1 class="page-title">{{ $greeting }} 👋</h1>
            <p class="page-sub">Resumo do seu escopo — {{ $scopeLabel }}</p>
        </div>
        <a href="{{ route('portal.exams.create') }}" class="btn btn-primary">+ Nova prova</a>
    </div>

    <div class="kpi-grid">
        <div class="card kpi">
            <div class="kpi-label">Provas aplicadas</div>
            <div class="kpi-value">{{ number_format($kpis['provas_aplicadas'], 0, ',', '.') }}</div>
            <div class="kpi-trend">no seu escopo</div>
        </div>
        <div class="card kpi">
            <div class="kpi-label">Cartões corrigidos</div>
            <div class="kpi-value">{{ number_format($kpis['cartoes_corrigidos'], 0, ',', '.') }}</div>
            <div class="kpi-trend">resultados vigentes</div>
        </div>
        <div class="card kpi">
            <div class="kpi-label">Média geral</div>
            <div class="kpi-value">{{ $kpis['media_geral'] !== null ? number_format($kpis['media_geral'], 1, ',', '.') : '—' }}</div>
            <div class="kpi-trend">escala 0–10</div>
        </div>
        <div class="card kpi">
            <div class="kpi-label">Pendências de leitura</div>
            <div class="kpi-value" @style(['color:var(--warn-fg)' => $kpis['pendencias'] > 0])>{{ $kpis['pendencias'] }}</div>
            <div class="kpi-trend" @style(['color:var(--warn-fg)' => $kpis['pendencias'] > 0])>
                {{ $kpis['pendencias'] > 0 ? '● aguardando revisão' : 'tudo em dia' }}
            </div>
        </div>
    </div>

    <div class="main-grid">
        <div class="card card-pad">
            <div class="chart-head">
                <h3>Desempenho médio por disciplina</h3>
                <div class="legend"><span><i style="background:#1351b4"></i>Acertos (%)</span></div>
            </div>
            @if (count($disciplinaSeries) > 0)
                <div data-bars='@json($disciplinaSeries)'></div>
            @else
                <div class="state-panel state-panel-compact">Sem resultados por disciplina ainda.</div>
            @endif
        </div>

        <div class="card card-pad">
            <h3 style="font-size:17px;margin-bottom:8px;">Atividade recente</h3>
            @if ($atividades->isNotEmpty())
                <ul class="activity">
                    @foreach ($atividades as $atividade)
                        @php([$icone, $bg, $cor] = $activityIcon($atividade->tipo))
                        <li>
                            <div class="act-ico" style="background:{{ $bg }};color:{{ $cor }}">{{ $icone }}</div>
                            <div>
                                <b>{{ $atividade->descricao }}</b>
                                <div class="act-time">{{ $atividade->created_at?->diffForHumans() }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="state-panel state-panel-compact">Nenhuma atividade recente.</div>
            @endif
        </div>
    </div>

    <h3 style="font-size:17px;margin-top:32px;">Ações rápidas</h3>
    <div class="quick-actions">
        <a class="qa" href="{{ route('portal.exams.create') }}"><div class="qa-ico">📝</div><b>Criar prova e gabarito</b><span>Monte o gabarito oficial e gere o cartão</span></a>
        <a class="qa" href="{{ route('portal.operations.index') }}"><div class="qa-ico">📷</div><b>Acompanhar correções</b><span>Leituras, revisões e confirmação</span></a>
        <a class="qa" href="{{ route('portal.classes.index') }}"><div class="qa-ico">👥</div><b>Gerenciar turmas</b><span>Alunos, matrículas e importação</span></a>
    </div>
    <div style="height:48px"></div>
    @endif
@endsection
