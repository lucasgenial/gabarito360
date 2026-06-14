<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1a1a1a; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .sub { color: #666; font-size: 10px; margin-bottom: 14px; }
        table.meta td { padding: 2px 4px; }
        table.meta td.label { color: #555; width: 30%; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid th, table.grid td { border: 1px solid #ccc; padding: 5px 8px; text-align: center; }
        table.grid th { background: #f2f2f2; }
        .anulada { color: #b00; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Gabarito Oficial</h1>
    <div class="sub">Gabarito360 — gerado em {{ $geradoEm->format('d/m/Y H:i') }}</div>

    <table class="meta">
        <tr><td class="label">Prova</td><td>{{ $prova->titulo }} ({{ $prova->codigo }})</td></tr>
        <tr><td class="label">Disciplina</td><td>{{ $prova->disciplina?->nome ?? '—' }}</td></tr>
        <tr><td class="label">Série</td><td>{{ $prova->serieAno?->nome ?? '—' }}</td></tr>
        <tr><td class="label">Questões</td><td>{{ $prova->quantidade_questoes }} ({{ $prova->quantidade_alternativas }} alternativas)</td></tr>
        <tr><td class="label">Versão do gabarito</td><td>{{ $gabarito?->versao ?? '—' }}</td></tr>
    </table>

    @if ($respostas->isEmpty())
        <p>Nenhuma resposta oficial registrada.</p>
    @else
        <table class="grid">
            <thead><tr><th>Questão</th><th>Resposta</th></tr></thead>
            <tbody>
            @foreach ($respostas as $resposta)
                <tr>
                    <td>{{ $resposta->questao?->numero }}</td>
                    <td>
                        @if ($resposta->anulada)
                            <span class="anulada">ANULADA</span>
                        @else
                            {{ $resposta->alternativa_correta }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
