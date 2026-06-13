# Telas: Resultado Individual (`resultado.html`, `resultado-dinamico.html`)

- **Rotas web:** `/resultados/{aluno}/{prova}` (com `?from=` para breadcrumb
  contextual). `resultado-dinamico.html` é a variante parametrizada/dinâmica;
  consolidam a **união** de capacidades.
- **Módulo:** Resultados
- **Atores/permissões:** professor, coordenação, gestão; aluno (próprio resultado).
- **Objetivo:** exibir o resultado de um aluno em uma prova — nota, acertos,
  desempenho por tema e a folha de respostas corrigida pelo OMR.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** breadcrumb **dinâmico** (varia conforme origem: prova, aluno,
  relatório de turma); avatar + nome + info (turma · prova · data); botões
  **"Exportar PDF"** e **"Revisar leitura"**.
- **Resumo de desempenho:** **donut** da nota + métricas (Nota final, Acertos,
  Questões) + badges (status Aprovada/Recuperação, comparação com a média da turma).
- **Acertos por tema:** gráfico de barras por tema/habilidade.
- **Folha de respostas corrigida:** grade questão a questão com legenda
  Correta / Incorreta / Em branco·ambígua, e badge "Leitura OMR · confiança %".

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra |
|---|---|---|---|---|
| Exportar PDF | botão | exporta resultado | `GET /api/v2/resultados/{id}.pdf` | autorizado/auditado |
| Revisar leitura | botão | abre revisão da leitura | `/correcao/...` / `POST leituras/{id}/revisao` | permissão; auditado |
| Folha de respostas | leitura | mostra correção | `GET /api/v2/resultados/{id}` | gabarito × marcado |
| Filtro de período (variante dinâmica) | select | recorta avaliações | `?periodo=` | — |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Nota, acertos, questões | `resultados`, `resultados_questoes` | nota = f(acertos, pesos) |
| Acertos por tema | `resultados_questoes` × `temas_habilidades` | barras acessíveis |
| Folha corrigida | gabarito × `respostas_detectadas` | correta/incorreta/branco |
| Confiança OMR | `processamentos_omr`/`metricas_omr` | % por leitura |
| Comparação com a média | agregação da turma | badge |

## Estados

`default`, `loading`, `error`, `success` (export), `access_denied`. Nota < 6 →
status "Recuperação" (cor danger). Questão em branco/ambígua destacada (warn).

## Regras de negócio

- A nota usa a versão do gabarito da prova e a política de pontuação/anulação.
- Aluno acessa somente o próprio resultado.
- "Revisar leitura" reabre a conferência da leitura (auditada e idempotente).
- Exportações autorizadas e auditadas.

## Responsividade

`result-head` 2→1 coluna; folha de respostas em grade fluida; sem overflow.

## Endpoints `/api/v2` necessários

- `GET /api/v2/resultados/{id}` — resultado completo + folha corrigida.
- `GET /api/v2/resultados/{id}.pdf` — exportação.
- `POST /api/v2/leituras/{leitura}/revisao` — revisar leitura.

## Pendências/decisões

- Consolidar `resultado.html` e `resultado-dinamico.html` numa única tela
  parametrizada por origem (`from`) e período.
- Definir alternativa acessível para donut e barras.
