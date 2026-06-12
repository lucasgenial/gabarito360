@extends('layouts.admin')

@section('title', 'Painel')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Visao contextual</p>
        <h1>Painel Gabarito360</h1>
        <p>Indicadores calculados com dados reais dentro do seu escopo autorizado.</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Escolas visiveis" :value="$metrics['escolas']" context="Escopo atual" variant="info" />
        <x-ui.kpi label="Turmas visiveis" :value="$metrics['turmas']" context="Escopo atual" variant="success" />
        <x-ui.kpi label="Alunos visiveis" :value="$metrics['alunos']" context="Dados minimizados" variant="info" />
        <x-ui.kpi label="Provas disponiveis" :value="$metrics['provas']" context="Publicadas ou gerenciaveis" variant="warning" />
        <x-ui.kpi label="Aplicacoes" :value="$metrics['aplicacoes']" context="Todas as situacoes" variant="success" />
        <x-ui.kpi label="Resultados vigentes" :value="$metrics['resultados']" context="Dentro do escopo" variant="info" />
    </div>

    <div class="content-grid">
        @if ($statusSeries !== [])
            <x-ui.chart id="aplicacoes-status" title="Aplicacoes por situacao" :series="$statusSeries" value-label="Aplicacoes" />
        @else
            <x-ui.card>
                <x-ui.empty-state title="Sem aplicacoes no escopo">
                    A atividade operacional aparecera aqui quando as aplicacoes forem criadas.
                </x-ui.empty-state>
            </x-ui.card>
        @endif

        <x-ui.card labelledby="atividade-recente">
            <div class="section-heading">
                <div>
                    <h2 id="atividade-recente">Aplicacoes recentes</h2>
                    <p>Acompanhamento consolidado sem atualizacao em tempo real nesta etapa.</p>
                </div>
                @if ($recentApplications->isNotEmpty())
                    <x-ui.button :href="route('portal.operations.index')" variant="neutral" size="sm" wire:navigate>Ver correcoes</x-ui.button>
                @endif
            </div>
            <x-ui.table caption="Aplicacoes recentes">
                <thead>
                    <tr>
                        <th scope="col">Aplicacao</th>
                        <th scope="col">Turma</th>
                        <th scope="col">Progresso</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentApplications as $application)
                        <tr>
                            <td>
                                <a class="text-link" href="{{ route('portal.operations.show', $application) }}" wire:navigate>{{ $application->titulo }}</a>
                                <span class="cell-detail">{{ $application->prova->titulo }}</span>
                            </td>
                            <td>{{ $application->turma->nome }}</td>
                            <td>{{ $application->leituras_count }} leituras / {{ $application->alunos_count }} previstos</td>
                            <td><x-admin.status-badge :status="$application->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-ui.empty-state title="Sem atividade recente" compact>Nenhuma aplicacao autorizada foi encontrada.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
@endsection
