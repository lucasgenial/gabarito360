@extends('layouts.admin')

@section('title', 'Provas e turmas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Avaliacoes</p>
        <h1>Provas publicadas</h1>
        <p>Consulte o gabarito vigente e autorize explicitamente as turmas que podem receber cada prova.</p>
    </header>

    @forelse ($provas as $prova)
        <details class="accordion card" @if ($loop->first) open @endif>
            <summary class="accordion-summary">
                <span>
                    <strong>{{ $prova->titulo }}</strong>
                    <span class="cell-detail">{{ $prova->codigo }} · {{ $prova->nucleo?->nome ?? $prova->escola?->nome }}</span>
                </span>
                <x-admin.status-badge :status="$prova->status->value" />
            </summary>

            <div class="accordion-content">
                <section aria-labelledby="gabarito-{{ $prova->id }}">
                    <h2 id="gabarito-{{ $prova->id }}">Gabarito oficial</h2>
                    @php($gabarito = $prova->gabaritosOficiais->first())
                    <p>
                        @if ($gabarito)
                            Versao {{ $gabarito->versao }} vigente desde {{ $gabarito->publicado_at?->format('d/m/Y H:i') }}.
                        @else
                            Nenhum gabarito vigente encontrado.
                        @endif
                    </p>
                </section>

                <section aria-labelledby="vincular-{{ $prova->id }}">
                    <h2 id="vincular-{{ $prova->id }}">Vincular turma</h2>
                    <form class="form-grid" method="POST" action="{{ route('admin.provas.turmas.store', $prova) }}">
                        @csrf
                        <div class="field field-wide">
                            <label for="turma-{{ $prova->id }}">Turma autorizada</label>
                            <select id="turma-{{ $prova->id }}" name="turma_id" required>
                                <option value="">Selecione uma turma</option>
                                @foreach ($turmasPorProva[$prova->id] as $turma)
                                    <option value="{{ $turma->id }}">
                                        {{ $turma->escola->nome }} · {{ $turma->nome }} · {{ $turma->ano_letivo }}
                                    </option>
                                @endforeach
                            </select>
                            <x-admin.field-error name="turma_id" />
                        </div>
                        <div class="field">
                            <label for="data-prevista-{{ $prova->id }}">Data prevista opcional</label>
                            <input id="data-prevista-{{ $prova->id }}" name="data_prevista" type="date">
                            <x-admin.field-error name="data_prevista" />
                        </div>
                        <div class="form-actions field-wide">
                            <button class="button button-primary" type="submit">Vincular turma</button>
                        </div>
                    </form>
                </section>

                <section aria-labelledby="turmas-{{ $prova->id }}">
                    <h2 id="turmas-{{ $prova->id }}">Turmas vinculadas</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Escola</th>
                                    <th scope="col">Turma</th>
                                    <th scope="col">Data prevista</th>
                                    <th scope="col"><span class="sr-only">Acoes</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($prova->provaTurmas as $vinculo)
                                    <tr>
                                        <td>{{ $vinculo->turma->escola->nome }}</td>
                                        <td>{{ $vinculo->turma->nome }} · {{ $vinculo->turma->ano_letivo }}</td>
                                        <td>{{ $vinculo->data_prevista?->format('d/m/Y') ?? 'Nao informada' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.provas.turmas.destroy', [$prova, $vinculo->turma]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button button-danger button-sm" type="submit">Remover vinculo</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-state">Nenhuma turma vinculada a esta prova.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </details>
    @empty
        <section class="card empty-state">
            Nenhuma prova publicada esta disponivel no seu escopo.
        </section>
    @endforelse

    {{ $provas->links() }}
@endsection
