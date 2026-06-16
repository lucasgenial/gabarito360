@extends('layouts.app')

@section('title', 'Acompanhar correções')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('portal.dashboard') }}">Início</a>
        <span class="sep">/</span>
        <span>Correções</span>
    </div>

    <div class="row-between page-toolbar">
        <div>
            <div class="eyebrow">Operação</div>
            <h1 class="page-title">Acompanhar correções</h1>
            <p class="page-sub">Aplicações autorizadas com progresso persistido e atualização operacional.</p>
        </div>
        <span class="badge badge-info">{{ number_format($applications->total(), 0, ',', '.') }} aplicação(ões)</span>
    </div>

    <div class="card card-pad">
        <div class="chart-head">
            <h3>Aplicações para acompanhamento</h3>
            <span class="badge badge-muted">Escopo autorizado</span>
        </div>

        @if ($applications->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <caption class="sr-only">Aplicações para acompanhamento</caption>
                    <thead>
                        <tr>
                            <th scope="col">Aplicação</th>
                            <th scope="col">Turma</th>
                            <th scope="col">Leituras</th>
                            <th scope="col">Resultados</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>
                                    <a class="text-link" href="{{ route('portal.operations.show', $application) }}">{{ $application->titulo }}</a>
                                    <span class="cell-detail">{{ $application->prova->titulo }}</span>
                                </td>
                                <td>
                                    {{ $application->turma->nome }}
                                    <span class="cell-detail">{{ $application->escola->nome }}</span>
                                </td>
                                <td class="num">{{ $application->leituras_count }} / {{ $application->alunos_count }}</td>
                                <td class="num">{{ $application->resultados_count }}</td>
                                <td><span class="badge badge-muted">{{ str_replace('_', ' ', $application->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="state-panel state-panel-compact">Nenhuma aplicação autorizada para o seu usuário.</div>
        @endif

        @if ($applications->hasPages())
            <div class="pagination-bar">
                <div class="pagination-meta">
                    Mostrando {{ $applications->firstItem() }}-{{ $applications->lastItem() }} de {{ $applications->total() }} aplicações
                </div>
                <div class="pagination-actions">
                    @if ($applications->onFirstPage())
                        <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Anterior</span>
                    @else
                        <a class="btn btn-sm btn-secondary" href="{{ $applications->previousPageUrl() }}">Anterior</a>
                    @endif

                    <span class="pagination-current">Página {{ $applications->currentPage() }} de {{ $applications->lastPage() }}</span>

                    @if ($applications->hasMorePages())
                        <a class="btn btn-sm btn-secondary" href="{{ $applications->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
