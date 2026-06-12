@extends('layouts.admin')

@section('title', 'Correcao da aplicacao')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $aplicacao->turma->nome }}</p>
        <h1>{{ $aplicacao->titulo }}</h1>
        <p>{{ $aplicacao->prova->titulo }} / {{ $aplicacao->escola->nome }}</p>
    </header>

    <div data-application-realtime="{{ $aplicacao->id }}">
    <div class="dashboard-grid">
        <x-ui.kpi label="Alunos previstos" :value="$aplicacao->alunos_count" context="Snapshot da aplicacao" variant="info" />
        <x-ui.kpi label="Leituras recebidas" value="{{ $aplicacao->leituras_count }}" context="Persistidas" variant="success" metric="readings" />
        <x-ui.kpi label="Resultados vigentes" value="{{ $aplicacao->resultados->where('status', 'vigente')->count() }}" context="Calculados" variant="warning" metric="current_results" />
        <x-ui.kpi label="Requerem revisao" value="{{ $aplicacao->leituras->where('requer_revisao', true)->count() }}" context="Leituras sinalizadas" variant="danger" metric="pending_review" />
    </div>

    <div class="form-actions">
        @if ($canRun && $aplicacao->status === 'agendada')
            <form method="POST" action="{{ route('portal.operations.start', $aplicacao) }}">@csrf<x-ui.button type="submit">Iniciar aplicacao</x-ui.button></form>
        @endif
        @if ($canRun && $aplicacao->status === 'em_andamento')
            <form method="POST" action="{{ route('portal.operations.finish', $aplicacao) }}">@csrf<x-ui.button type="submit" variant="secondary">Finalizar aplicacao</x-ui.button></form>
        @endif
        @if ($canExport)
            <form method="POST" action="{{ route('portal.operations.report.csv', $aplicacao) }}">@csrf<x-ui.button type="submit" variant="neutral">Exportar CSV</x-ui.button></form>
        @endif
    </div>

    <x-ui.alert title="Fluxo operacional R6">
        Capturas recebidas pela API ou app aparecem abaixo. Leituras sinalizadas exigem revisao auditada antes da confirmacao.
    </x-ui.alert>

    <x-ui.card labelledby="alunos-aplicacao">
        <h2 id="alunos-aplicacao">Situacao por aluno</h2>
        <x-ui.table caption="Situacao dos alunos na aplicacao">
            <thead><tr><th scope="col">Aluno</th><th scope="col">Situacao</th><th scope="col">Resultado</th><th scope="col">Confirmacao</th></tr></thead>
            <tbody>
                @forelse ($aplicacao->alunos as $applicationStudent)
                    <tr>
                        <td>{{ $applicationStudent->aluno->nome }}</td>
                        <td><x-admin.status-badge :status="$applicationStudent->status" /></td>
                        <td>
                            @if ($applicationStudent->resultadoVigente && $canViewResults)
                                <a class="text-link" href="{{ route('portal.results.show', $applicationStudent->resultadoVigente) }}" wire:navigate>
                                    {{ number_format((float) $applicationStudent->resultadoVigente->nota_percentual, 2, ',', '.') }}%
                                </a>
                            @elseif ($applicationStudent->resultadoVigente)
                                Disponivel para perfil autorizado
                            @else
                                Nao calculado
                            @endif
                        </td>
                        <td>{{ $applicationStudent->confirmado_at?->format('d/m/Y H:i') ?? 'Pendente' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-ui.empty-state title="Snapshot vazio" compact>Nenhum aluno foi associado a aplicacao.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    <x-ui.card labelledby="leituras-aplicacao">
        <h2 id="leituras-aplicacao">Leituras recebidas</h2>
        @forelse ($aplicacao->leituras->sortByDesc('created_at') as $reading)
            <section class="card">
                <div class="section-heading">
                    <div>
                        <h3>{{ $reading->operacao_id }}</h3>
                        <p><x-admin.status-badge :status="$reading->status" /> Confianca: {{ $reading->confianca_geral ?? 'nao informada' }}</p>
                    </div>
                    @if ($canConfirm && ! $reading->requer_revisao && ! $reading->confirmada_at)
                        <form method="POST" action="{{ route('portal.operations.readings.confirm', $reading) }}">@csrf<x-ui.button type="submit" size="sm">Confirmar leitura</x-ui.button></form>
                    @endif
                </div>
                @if ($reading->requer_revisao && $canReview)
                    <form method="POST" action="{{ route('portal.operations.readings.review', $reading) }}">
                        @csrf
                        <div class="form-grid">
                            @foreach ($reading->respostasDetectadas as $answer)
                                <div class="field">
                                    <label for="answer-{{ $answer->id }}">Questao {{ $answer->questao->numero }}</label>
                                    <input id="answer-{{ $answer->id }}" name="respostas[{{ $answer->questao_id }}]" value="{{ $answer->alternativa_final }}" maxlength="10">
                                </div>
                            @endforeach
                            <div class="field field-wide">
                                <label for="reason-{{ $reading->id }}">Motivo da revisao</label>
                                <input id="reason-{{ $reading->id }}" name="motivo" required minlength="5" maxlength="500">
                            </div>
                        </div>
                        <div class="form-actions"><x-ui.button type="submit" size="sm">Registrar revisao</x-ui.button></div>
                    </form>
                @endif
            </section>
        @empty
            <x-ui.empty-state title="Nenhuma leitura recebida">As capturas sincronizadas pelo app aparecerao aqui.</x-ui.empty-state>
        @endforelse
    </x-ui.card>
    </div>
@endsection
