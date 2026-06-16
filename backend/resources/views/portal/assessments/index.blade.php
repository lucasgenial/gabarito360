@extends('layouts.app')

@section('title', 'Provas')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('portal.dashboard') }}">Início</a>
        <span class="sep">/</span>
        <span>Provas</span>
    </div>

    <div class="row-between page-toolbar">
        <div>
            <div class="eyebrow">Avaliações</div>
            <h1 class="page-title">Provas</h1>
            <p class="page-sub">Provas gerenciáveis, publicadas ou vinculadas às suas turmas.</p>
        </div>

        @can('viewAny', App\Models\Prova::class)
            <a href="{{ route('portal.exams.create') }}" class="btn btn-primary">Nova prova</a>
        @endcan
    </div>

    <div class="card card-pad">
        <div class="chart-head">
            <h3>Provas disponíveis</h3>
            <span class="badge badge-info">{{ number_format($exams->total(), 0, ',', '.') }} registro(s)</span>
        </div>

        @if ($exams->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <caption class="sr-only">Provas disponíveis</caption>
                    <thead>
                        <tr>
                            <th scope="col">Prova</th>
                            <th scope="col">Contexto</th>
                            <th scope="col">Questões</th>
                            <th scope="col">Aplicações</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td>
                                    <a class="text-link" href="{{ route('portal.exams.show', $exam) }}">{{ $exam->titulo }}</a>
                                    <span class="cell-detail">{{ $exam->codigo }}</span>
                                </td>
                                <td>
                                    {{ $exam->disciplina?->nome ?? $exam->tipo }}
                                    <span class="cell-detail">{{ $exam->serieAno?->nome ?? $exam->nivel ?? 'Série não definida' }}</span>
                                </td>
                                <td class="num">{{ $exam->questoes_count }}</td>
                                <td class="num">{{ $exam->aplicacoes_count }}</td>
                                <td><span class="badge badge-muted">{{ $exam->status->value }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="state-panel state-panel-compact">Nenhuma prova disponível no seu escopo.</div>
        @endif

        @if ($exams->hasPages())
            <div class="pagination-bar">
                <div class="pagination-meta">
                    Mostrando {{ $exams->firstItem() }}-{{ $exams->lastItem() }} de {{ $exams->total() }} provas
                </div>
                <div class="pagination-actions">
                    @if ($exams->onFirstPage())
                        <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Anterior</span>
                    @else
                        <a class="btn btn-sm btn-secondary" href="{{ $exams->previousPageUrl() }}">Anterior</a>
                    @endif

                    <span class="pagination-current">Página {{ $exams->currentPage() }} de {{ $exams->lastPage() }}</span>

                    @if ($exams->hasMorePages())
                        <a class="btn btn-sm btn-secondary" href="{{ $exams->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @can('viewClassLinksAny', App\Models\Prova::class)
        <div class="card card-pad notice-card">
            <strong>Gestão de vínculos</strong>
            <span>
                O fluxo administrativo entre provas publicadas e turmas continua disponível em
                <a class="text-link" href="{{ route('admin.provas.index') }}">vínculos de provas</a>.
            </span>
        </div>
    @endcan
@endsection
