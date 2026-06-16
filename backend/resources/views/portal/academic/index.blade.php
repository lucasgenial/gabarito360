@extends('layouts.app')

@section('title', 'Turmas')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('portal.dashboard') }}">Início</a>
        <span class="sep">/</span>
        <span>Turmas</span>
    </div>

    <div class="row-between page-toolbar">
        <div>
            <div class="eyebrow">Estrutura acadêmica</div>
            <h1 class="page-title">Turmas</h1>
            <p class="page-sub">Turmas autorizadas para consulta e gestão no contexto atual.</p>
        </div>
        <span class="badge badge-info">{{ number_format($classes->total(), 0, ',', '.') }} turma(s)</span>
    </div>

    @if ($schools->isNotEmpty())
        <div class="card card-pad">
            <div class="chart-head">
                <h3 id="nova-turma">Nova turma</h3>
                <span class="badge badge-muted">Cadastro autorizado</span>
            </div>

            <form class="form-grid" method="POST" action="{{ route('portal.classes.store') }}">
                @csrf

                <div class="field field-wide">
                    <label for="escola_id">Escola</label>
                    <select class="select" id="escola_id" name="escola_id" required>
                        <option value="">Selecione uma escola</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected(old('escola_id') === $school->id)>{{ $school->nome }} / {{ $school->nucleo->nome }}</option>
                        @endforeach
                    </select>
                    @error('escola_id')<div class="field-help">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="codigo">Código</label>
                    <input class="input" id="codigo" name="codigo" value="{{ old('codigo') }}" required maxlength="50">
                    @error('codigo')<div class="field-help">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nome">Nome</label>
                    <input class="input" id="nome" name="nome" value="{{ old('nome') }}" required maxlength="120">
                    @error('nome')<div class="field-help">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="serie_ano">Série ou ano</label>
                    <input class="input" id="serie_ano" name="serie_ano" value="{{ old('serie_ano') }}" required maxlength="60">
                    @error('serie_ano')<div class="field-help">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="turno">Turno</label>
                    <select class="select" id="turno" name="turno">
                        <option value="">Não informado</option>
                        @foreach (['matutino' => 'Matutino', 'vespertino' => 'Vespertino', 'noturno' => 'Noturno', 'integral' => 'Integral'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('turno') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('turno')<div class="field-help">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="ano_letivo">Ano letivo</label>
                    <input class="input" id="ano_letivo" name="ano_letivo" type="number" value="{{ old('ano_letivo', now()->year) }}" required min="2000" max="2100">
                    @error('ano_letivo')<div class="field-help">{{ $message }}</div>@enderror
                </div>

                <div class="form-actions field-wide">
                    <button class="btn btn-primary" type="submit">Criar turma</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card card-pad">
        <div class="chart-head">
            <h3 id="lista-turmas">Turmas visíveis</h3>
            <span class="badge badge-muted">Escopo aplicado</span>
        </div>

        @if ($classes->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <caption class="sr-only">Turmas visíveis</caption>
                    <thead>
                        <tr>
                            <th scope="col">Turma</th>
                            <th scope="col">Escola</th>
                            <th scope="col">Ano</th>
                            <th scope="col">Alunos</th>
                            <th scope="col">Aplicações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classes as $class)
                            <tr>
                                <td>
                                    <a class="text-link" href="{{ route('portal.classes.show', $class) }}">{{ $class->nome }}</a>
                                    <span class="cell-detail">{{ $class->codigo }} / {{ $class->serie_ano }}</span>
                                </td>
                                <td>{{ $class->escola->nome }}</td>
                                <td class="num">{{ $class->ano_letivo }}</td>
                                <td class="num">{{ $class->matriculas_count }}</td>
                                <td class="num">{{ $class->aplicacoes_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="state-panel state-panel-compact">Seu escopo ainda não possui turmas.</div>
        @endif

        @if ($classes->hasPages())
            <div class="pagination-bar">
                <div class="pagination-meta">
                    Mostrando {{ $classes->firstItem() }}-{{ $classes->lastItem() }} de {{ $classes->total() }} turmas
                </div>
                <div class="pagination-actions">
                    @if ($classes->onFirstPage())
                        <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Anterior</span>
                    @else
                        <a class="btn btn-sm btn-secondary" href="{{ $classes->previousPageUrl() }}">Anterior</a>
                    @endif

                    <span class="pagination-current">Página {{ $classes->currentPage() }} de {{ $classes->lastPage() }}</span>

                    @if ($classes->hasMorePages())
                        <a class="btn btn-sm btn-secondary" href="{{ $classes->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
