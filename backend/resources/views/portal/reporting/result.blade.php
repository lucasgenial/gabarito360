@extends('layouts.admin')

@section('title', 'Resultado')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $resultado->prova->titulo }}</p>
        <h1>Resultado de {{ $resultado->aluno->nome }}</h1>
        <p>{{ $resultado->aplicacao->turma->nome }} / calculado em {{ $resultado->calculado_at->format('d/m/Y H:i') }}</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Nota" value="{{ number_format((float) $resultado->nota_percentual, 2, ',', '.') }}%" context="Percentual" variant="success" />
        <x-ui.kpi label="Acertos" :value="$resultado->acertos" context="Questoes corretas" variant="success" />
        <x-ui.kpi label="Erros" :value="$resultado->erros" context="Questoes incorretas" variant="danger" />
        <x-ui.kpi label="Brancos ou duplas" :value="$resultado->brancos + $resultado->duplas" context="Requer atencao" variant="warning" />
    </div>

    <x-ui.card labelledby="respostas-resultado">
        <h2 id="respostas-resultado">Respostas consolidadas</h2>
        <x-ui.table caption="Respostas consolidadas">
            <thead><tr><th scope="col">Questao</th><th scope="col">Resposta</th><th scope="col">Situacao</th><th scope="col">Pontuacao</th></tr></thead>
            <tbody>
                @forelse ($resultado->questoes->sortBy(fn ($item) => $item->questao->numero) as $item)
                    <tr>
                        <td>{{ $item->questao->numero }}</td>
                        <td>{{ $item->resposta_final ?? '-' }}</td>
                        <td><x-admin.status-badge :status="$item->situacao" /></td>
                        <td>{{ number_format((float) $item->pontuacao, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-ui.empty-state title="Sem detalhamento" compact>O resultado nao possui itens consolidados.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
@endsection
