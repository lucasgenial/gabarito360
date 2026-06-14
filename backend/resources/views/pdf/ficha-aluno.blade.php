<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1a1a1a; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .sub { color: #666; font-size: 10px; margin-bottom: 16px; }
        .sec { margin-bottom: 14px; }
        .sec h2 { font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 4px; vertical-align: top; }
        td.label { color: #555; width: 32%; }
        .kpi { color: #555; }
    </style>
</head>
<body>
    <h1>Ficha do Aluno</h1>
    <div class="sub">Gabarito360 — gerada em {{ $geradoEm->format('d/m/Y H:i') }}</div>

    <div class="sec">
        <h2>Dados pessoais</h2>
        <table>
            <tr><td class="label">Nome</td><td>{{ $aluno->nome }}</td></tr>
            @if ($aluno->nome_social)
                <tr><td class="label">Nome social</td><td>{{ $aluno->nome_social }}</td></tr>
            @endif
            <tr><td class="label">Matrícula</td><td>{{ $aluno->matricula }}</td></tr>
            <tr><td class="label">Data de nascimento</td><td>{{ optional($aluno->data_nascimento)->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><td class="label">CPF</td><td>{{ $aluno->documento ?? '—' }}</td></tr>
            <tr><td class="label">Gênero</td><td>{{ $aluno->genero ?? '—' }}</td></tr>
            <tr><td class="label">Situação</td><td>{{ $aluno->status->value }}</td></tr>
        </table>
    </div>

    <div class="sec">
        <h2>Vínculo escolar</h2>
        <table>
            <tr><td class="label">Escola</td><td>{{ $escola?->nome ?? '—' }}</td></tr>
            <tr><td class="label">Turma</td><td>{{ $turma?->nome ?? '—' }}</td></tr>
            <tr><td class="label">Série</td><td>{{ $turma?->serie_ano ?? '—' }}</td></tr>
            <tr><td class="label">Turno</td><td>{{ $turma?->turno ?? '—' }}</td></tr>
            <tr><td class="label">Ano letivo</td><td>{{ $turma?->ano_letivo ?? '—' }}</td></tr>
            <tr><td class="label">Nº de chamada</td><td>{{ $matricula?->numero_chamada ?? '—' }}</td></tr>
        </table>
    </div>

    <div class="sec">
        <h2>Responsável</h2>
        <table>
            <tr><td class="label">Nome</td><td>{{ $responsavel?->nome ?? '—' }}</td></tr>
            <tr><td class="label">Telefone</td><td>{{ $responsavel?->telefone ?? '—' }}</td></tr>
            <tr><td class="label">E-mail</td><td>{{ $responsavel?->email ?? '—' }}</td></tr>
        </table>
    </div>

    <div class="sec">
        <h2>Indicadores</h2>
        <p class="kpi">Média geral e frequência: indisponíveis até a apuração de resultados.</p>
    </div>
</body>
</html>
