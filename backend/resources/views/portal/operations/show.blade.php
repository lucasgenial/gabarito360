@extends('layouts.admin')

@section('title', 'Correcao da aplicacao')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $aplicacao->turma->nome }}</p>
        <h1>{{ $aplicacao->titulo }}</h1>
        <p>{{ $aplicacao->prova->titulo }} / {{ $aplicacao->escola->nome }}</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Alunos previstos" :value="$aplicacao->alunos_count" context="Snapshot da aplicacao" variant="info" />
        <x-ui.kpi label="Leituras recebidas" :value="$aplicacao->leituras_count" context="Persistidas" variant="success" />
        <x-ui.kpi label="Resultados" :value="$aplicacao->resultados_count" context="Calculados" variant="warning" />
        <x-ui.kpi label="Requerem revisao" :value="$aplicacao->leituras->where('requer_revisao', true)->count()" context="Leituras sinalizadas" variant="danger" />
    </div>

    <x-ui.alert title="Limite operacional do R5">
        Esta tela consulta dados reais. Captura, revisao, confirmacao, correcao e atualizacao em tempo real serao integradas no R6.
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
@endsection
