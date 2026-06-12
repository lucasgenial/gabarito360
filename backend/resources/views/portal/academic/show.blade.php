@extends('layouts.admin')

@section('title', $turma->nome)

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $turma->escola->nome }}</p>
        <h1>{{ $turma->nome }}</h1>
        <p>{{ $turma->serie_ano }} / {{ ucfirst($turma->turno ?? 'turno nao informado') }} / {{ $turma->ano_letivo }}</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Matriculas" :value="$turma->matriculas->count()" context="Historico da turma" variant="success" />
        <x-ui.kpi label="Equipe vinculada" :value="$turma->aplicadores->whereNull('fim_em')->count()" context="Vinculos vigentes" variant="info" />
        <x-ui.kpi label="Provas vinculadas" :value="$turma->provaTurmas->count()" context="Disponiveis" variant="warning" />
        <x-ui.kpi label="Aplicacoes" :value="$turma->aplicacoes->count()" context="Registradas" variant="info" />
    </div>

    <x-ui.tabs label="Detalhes da turma">
        <x-slot:tabs>
            <x-ui.tab id="tab-alunos" panel="panel-alunos" selected>Alunos</x-ui.tab>
            <x-ui.tab id="tab-provas" panel="panel-provas">Provas e aplicacoes</x-ui.tab>
            <x-ui.tab id="tab-importacao" panel="panel-importacao">Importacao</x-ui.tab>
        </x-slot:tabs>

        <x-ui.tab-panel id="panel-alunos" tab="tab-alunos" active>
            <div class="section-heading">
                <div><h2>Alunos matriculados</h2><p>Dados pessoais reduzidos ao necessario.</p></div>
                @can('update', $turma)
                    <x-ui.button :href="route('portal.students.create', $turma)" wire:navigate>Novo aluno</x-ui.button>
                @endcan
            </div>
            <x-ui.table caption="Alunos matriculados">
                <thead><tr><th scope="col">Chamada</th><th scope="col">Aluno</th><th scope="col">Matricula</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @forelse ($turma->matriculas as $enrollment)
                        <tr>
                            <td>{{ $enrollment->numero_chamada ?? '-' }}</td>
                            <td><a class="text-link" href="{{ route('portal.students.show', $enrollment->aluno) }}" wire:navigate>{{ $enrollment->aluno->nome }}</a></td>
                            <td>{{ $enrollment->aluno->matricula }}</td>
                            <td><x-admin.status-badge :status="$enrollment->status->value" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-ui.empty-state title="Sem alunos" compact>Cadastre ou importe alunos para esta turma.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.tab-panel>

        <x-ui.tab-panel id="panel-provas" tab="tab-provas">
            <h2>Provas vinculadas</h2>
            <x-ui.table caption="Provas vinculadas">
                <thead><tr><th scope="col">Prova</th><th scope="col">Data prevista</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @forelse ($turma->provaTurmas as $link)
                        <tr>
                            <td><a class="text-link" href="{{ route('portal.exams.show', $link->prova) }}" wire:navigate>{{ $link->prova->titulo }}</a></td>
                            <td>{{ $link->data_prevista?->format('d/m/Y') ?? 'Nao informada' }}</td>
                            <td><x-admin.status-badge :status="$link->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Sem provas vinculadas" compact>Use o fluxo administrativo de vinculos.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.tab-panel>

        <x-ui.tab-panel id="panel-importacao" tab="tab-importacao">
            @can('update', $turma)
                <h2>Validar arquivo CSV</h2>
                <p>O arquivo e inspecionado antes da confirmacao controlada.</p>
                <form class="form-grid" method="POST" action="{{ route('portal.students.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="escola_id" value="{{ $turma->escola_id }}">
                    <input type="hidden" name="turma_id" value="{{ $turma->id }}">
                    <x-ui.input name="arquivo" label="Arquivo CSV" type="file" required accept=".csv,text/csv" wide />
                    <div class="form-actions field-wide"><x-ui.button type="submit">Validar importacao</x-ui.button></div>
                </form>
            @endcan
            <h2>Importacoes recentes</h2>
            <x-ui.table caption="Importacoes recentes">
                <thead><tr><th scope="col">Arquivo</th><th scope="col">Situacao</th><th scope="col">Data</th></tr></thead>
                <tbody>
                    @forelse ($turma->importacoesAlunos as $import)
                        <tr><td>{{ $import->arquivo_nome }}</td><td><x-admin.status-badge :status="$import->status->value" /></td><td>{{ $import->created_at->format('d/m/Y H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Sem importacoes" compact>Nenhum CSV foi enviado para esta turma.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.tab-panel>
    </x-ui.tabs>
@endsection
