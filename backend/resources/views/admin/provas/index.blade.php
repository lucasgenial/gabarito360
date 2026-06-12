@extends('layouts.admin')

@section('title', 'Provas e turmas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Avaliacoes</p>
        <h1>Provas publicadas</h1>
        <p>Consulte o gabarito vigente e autorize explicitamente as turmas que podem receber cada prova.</p>
    </header>

    @forelse ($provas as $prova)
        <x-ui.accordion :open="$loop->first">
            <x-slot:summary>
                <span>
                    <strong>{{ $prova->titulo }}</strong>
                    <span class="cell-detail">{{ $prova->codigo }} · {{ $prova->nucleo?->nome ?? $prova->escola?->nome }}</span>
                </span>
                <x-admin.status-badge :status="$prova->status->value" />
            </x-slot:summary>

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
                        <x-ui.select name="turma_id" label="Turma autorizada" id="turma-{{ $prova->id }}" required wide>
                                <option value="">Selecione uma turma</option>
                                @foreach ($turmasPorProva[$prova->id] as $turma)
                                    <option value="{{ $turma->id }}">
                                        {{ $turma->escola->nome }} · {{ $turma->nome }} · {{ $turma->ano_letivo }}
                                    </option>
                                @endforeach
                        </x-ui.select>
                        <x-ui.date-picker name="data_prevista" label="Data prevista opcional" id="data-prevista-{{ $prova->id }}" />
                        <div class="form-actions field-wide">
                            <x-ui.button type="submit">Vincular turma</x-ui.button>
                        </div>
                    </form>
                </section>

                <section aria-labelledby="turmas-{{ $prova->id }}">
                    <h2 id="turmas-{{ $prova->id }}">Turmas vinculadas</h2>
                    <x-ui.table caption="Turmas vinculadas a {{ $prova->titulo }}">
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
                                                <x-ui.button type="submit" variant="danger" size="sm">Remover vinculo</x-ui.button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><x-ui.empty-state title="Nenhuma turma vinculada" compact>Vincule uma turma autorizada para continuar.</x-ui.empty-state></td></tr>
                                @endforelse
                            </tbody>
                    </x-ui.table>
                </section>
        </x-ui.accordion>
    @empty
        <x-ui.card>
            <x-ui.empty-state title="Nenhuma prova publicada">
                Nenhuma prova publicada esta disponivel no seu escopo.
            </x-ui.empty-state>
        </x-ui.card>
    @endforelse

    <x-ui.pagination :paginator="$provas" />
@endsection
