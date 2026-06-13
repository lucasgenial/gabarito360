# Tela: Detalhe da Turma (`turma-detalhe-2.html`)

- **Rota web:** `/turmas/{id}`
- **Módulo:** Turmas
- **Atores/permissões:** gestão escolar, coordenação, professor da turma. Escopo
  obrigatório.
- **Objetivo:** apresentar indicadores, provas e alunos de uma turma, com acesso
  a relatórios, acompanhamento de correção e perfis dos alunos.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** breadcrumb (Turmas / nome), título da turma + meta
  (série · desempenho médio); botões **"Relatório Geral"** e **"+ Adicionar aluno"**
  (leva ao cadastro com a turma pré-selecionada).
- **KPI grid (4):** Desempenho médio, Provas aplicadas (bimestre), Alunos ativos,
  Frequência média (com meta da rede).
- **Bloco de gráficos:** "Desempenho por disciplina (Turma)" (barras `data-bars`)
  + "Participação em Provas" (donut `data-donut`) — exigem alternativa acessível.
- **Provas da Turma:** tabela (Prova, Aplicação, Status, Média/Progresso, ação):
  corrigida → **Relatório** (`/relatorios/turma-prova`); em correção → mini-bar de
  progresso + **Acompanhar** (`/correcao/turma`).
- **Alunos da Turma:** toolbar (busca + filtro de status Aprovado/Recuperação/
  Pendente) e tabela (Aluno, Provas, Desempenho [mini-bar], Status, ação **Perfil**).
  Empty state quando filtros não casam. Linha clicável leva ao detalhe do aluno.

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra |
|---|---|---|---|---|
| Relatório Geral | botão | relatório da turma | `/relatorios/turma/{id}` | permissão |
| + Adicionar aluno | link | cadastro com turma pré-selecionada | `/alunos/novo?turma={id}` | escopo |
| Buscar aluno | input | filtra alunos | `GET /api/v2/turmas/{id}/alunos?q=` | atualiza contagem |
| Filtro status (aluno) | select | aprovado/recuperação/pendente | `?status=` | — |
| Relatório (prova corrigida) | link | relatório turma×prova | `/relatorios/turma-prova/{...}` | só corrigidas |
| Acompanhar (em correção) | link | acompanhamento por turma | `/correcao/turma/{...}` | só em correção |
| Ver perfil / linha do aluno | link | detalhe do aluno | `/alunos/{id}?turma={id}` | — |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| KPIs (média, provas, alunos, frequência) | agregações da turma | reais; meta configurável |
| Gráficos disciplina/participação | `resultados`, `aplicacoes_alunos` | alternativa acessível |
| Provas da turma (status, média, progresso) | `provas`, `aplicacoes`, `leituras` | progresso = lidos/total |
| Alunos (provas, desempenho, status) | `matriculas_turmas`, `resultados` | status derivado |

## Estados

`default`, `hover`, `focus`, `loading`, `empty` (sem alunos/provas no filtro),
`error`, `success`, `access_denied`. Aluno pendente: desempenho exibido como "—".

## Regras de negócio

- Todo o conteúdo é restrito à turma e ao escopo do ator.
- Provas "em correção" mostram progresso (cartões lidos / total) e levam ao
  acompanhamento; "corrigidas" levam ao relatório.
- Status do aluno (aprovado/recuperação/pendente) derivado das notas.
- Adicionar aluno herda a turma do contexto.

## Responsividade

KPI grid 4→2→1; bloco de gráficos vira 1 coluna ≤980px; colunas `hide-sm`
ocultas ≤720px; rótulos de ação ocultos ≤900px. Sem overflow horizontal.

## Endpoints `/api/v2` necessários

- `GET /api/v2/turmas/{id}` — cabeçalho e meta.
- `GET /api/v2/turmas/{id}/indicadores` — KPIs e gráficos (com payload acessível).
- `GET /api/v2/turmas/{id}/provas` — provas da turma com status/progresso.
- `GET /api/v2/turmas/{id}/alunos?q=&status=` — alunos da turma.

## Pendências/decisões

- Fonte da "frequência média" (módulo de frequência — `frequencias`).
- Alternativa acessível para barras e donut.
