@extends('layouts.admin')

@section('title', 'Gabarito da prova')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $prova->codigo }}</p>
        <h1>Gabarito oficial</h1>
        <p>{{ $prova->titulo }} / historico de versoes persistido.</p>
    </header>

    @forelse ($prova->gabaritosOficiais as $answerKey)
        <x-ui.accordion :open="$loop->first">
            <x-slot:summary>
                <span><strong>Versao {{ $answerKey->versao }}</strong><span class="cell-detail">{{ $answerKey->publicado_at?->format('d/m/Y H:i') ?? 'Ainda nao publicado' }}</span></span>
                <x-admin.status-badge :status="$answerKey->status->value" />
            </x-slot:summary>
            <x-ui.table caption="Respostas da versao {{ $answerKey->versao }}">
                <thead><tr><th scope="col">Questao</th><th scope="col">Resposta</th><th scope="col">Peso</th><th scope="col">Situacao</th></tr></thead>
                <tbody>
                    @forelse ($answerKey->respostas->sortBy(fn ($answer) => $answer->questao->numero) as $answer)
                        <tr>
                            <td>{{ $answer->questao->numero }}</td>
                            <td>{{ $answer->alternativa_correta ?? '-' }}</td>
                            <td>{{ number_format((float) $answer->peso, 2, ',', '.') }}</td>
                            <td>{{ $answer->anulada ? 'Anulada' : 'Valida' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-ui.empty-state title="Gabarito incompleto" compact>Nenhuma resposta foi cadastrada.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.accordion>
    @empty
        <x-ui.card><x-ui.empty-state title="Nenhum gabarito cadastrado">A prova ainda nao possui uma versao de gabarito.</x-ui.empty-state></x-ui.card>
    @endforelse
@endsection
