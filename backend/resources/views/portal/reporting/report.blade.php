@extends('layouts.admin')

@section('title', $title)

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Relatorios</p>
        <h1>{{ $title }}</h1>
        <p>{{ $context }}</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Resultados" :value="$summary['count']" context="Vigentes e autorizados" variant="info" />
        <x-ui.kpi label="Media" value="{{ number_format($summary['average'], 2, ',', '.') }}%" context="Nota percentual" variant="success" />
        <x-ui.kpi label="Maior nota" value="{{ number_format($summary['highest'], 2, ',', '.') }}%" context="Nota percentual" variant="success" />
        <x-ui.kpi label="Menor nota" value="{{ number_format($summary['lowest'], 2, ',', '.') }}%" context="Nota percentual" variant="warning" />
    </div>

    <x-ui.alert title="Exportacoes">
        A consulta usa resultados reais. Geracao de CSV/PDF e processamento assincrono permanecem no R6.
    </x-ui.alert>

    <x-ui.card labelledby="resultados-relatorio">
        <h2 id="resultados-relatorio">Resultados autorizados</h2>
        <x-ui.table caption="Resultados autorizados">
            <thead><tr><th scope="col">Aluno</th><th scope="col">Turma</th><th scope="col">Acertos</th><th scope="col">Nota</th><th scope="col">Situacao</th></tr></thead>
            <tbody>
                @forelse ($results as $result)
                    <tr>
                        <td><a class="text-link" href="{{ route('portal.results.show', $result) }}" wire:navigate>{{ $result->aluno->nome }}</a></td>
                        <td>{{ $result->aplicacao->turma->nome }}</td>
                        <td>{{ $result->acertos }}</td>
                        <td>{{ number_format((float) $result->nota_percentual, 2, ',', '.') }}%</td>
                        <td><x-admin.status-badge :status="$result->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-ui.empty-state title="Sem resultados consolidados" compact>O relatorio sera preenchido quando houver resultados autorizados.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
@endsection
