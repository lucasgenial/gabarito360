# Tela: Detalhe da Escola (`escola-detalhe.html`)

- **Rota web:** `/escolas/{id}` (com aba via hash, ex.: `#equipe`)
- **Módulo:** Escolas
- **Atores/permissões:** admin, gestor de núcleo (suas escolas), gestão escolar
  (sua escola). Escopo obrigatório.
- **Objetivo:** apresentar a visão completa de uma escola e suas abas de turmas,
  provas, alunos e equipe.
- **Shell:** ver [`_shell.md`](_shell.md). Aqui o breadcrumb fica no **hero**.

## Layout e componentes

- **Hero** (gradiente accent): ícone, nome, metadados (INEP, endereço, status,
  rede) e **ações** (Voltar, Editar escola, Ver turmas, Ver provas).
- **Abas** (`tabs-row`, `role=tablist`): **Visão Geral**, **Turmas**, **Provas**,
  **Alunos**, **Equipe**. Painéis `tab-panel` alternados por JS.

### Aba Visão Geral
- **KPI strip (5):** Alunos ativos, Turmas, Provas aplicadas, Média geral, Taxa
  de aprovação (com delta ↑/↓/neutro).
- **Desempenho por disciplina:** barras (valor por disciplina, cores por faixa).
- **Evolução da média (ano):** sparkline SVG (precisa alternativa acessível).
- **Dados da escola:** card de info (endereço, CEP, telefone, e-mail, rede, INEP,
  fundação, status).
- **Distribuição de notas:** donut (Excelente/Bom/Regular/Abaixo) + legenda.
- **Provas recentes:** lista (prova, turma/alunos/data, status, nota) + "Ver todas".

### Aba Turmas
Tabela: Turma, Série, Turno, Professor(a), Alunos, Média, Status, ação **Ver**
(→ detalhe da turma) + botão **+ Nova turma**.

### Aba Provas
Tabela: Prova, Disciplina, Turma, Aplicação, Alunos, Média, Status, ação
**Relatório**/**Acompanhar** + botão **+ Nova prova**.

### Aba Alunos
Cabeçalho com contagem, **busca** e **+ Cadastrar**. Tabela: Aluno, Matrícula,
Turma, Média geral, Provas, Status, ação **Ver**. Rodapé "Exibindo N de M".

### Aba Equipe
Botões **Gerenciar perfis** e **+ Adicionar membro**. Agrupado por **Direção** e
**Professores** em cards (avatar, nome, função/contato, **Editar**).

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra |
|---|---|---|---|---|
| Abas | tabs | troca painel | — | `aria-selected`; lazy-load por aba |
| Editar escola (hero) | botão | editar | `/escolas/{id}` (modal/edição) | permissão |
| Ver turmas/provas (hero) | link | navega filtrando pela escola | `/turmas?escola=`, `/provas?escola=` | escopo |
| Busca de alunos | input | filtra alunos da escola | `GET /api/v2/escolas/{id}/alunos?q=` | — |
| + Nova turma/prova/Cadastrar | link | criação no escopo da escola | rotas de criação | permissão |
| Ver (turma/aluno) | link | detalhe | `/turmas/{id}`, `/alunos/{id}` | — |
| Gerenciar perfis | link | `/escolas/{id}/perfis` | — | gestão escolar |
| Adicionar/Editar membro | link | `/escolas/{id}/membros/...` | — | gestão escolar |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Hero (nome, INEP, endereço, status, rede) | `escolas` | id via rota |
| KPIs e gráficos | agregações de `resultados`, `alunos`, `turmas`, `provas` | reais |
| Tabelas (turmas/provas/alunos/equipe) | respectivas tabelas no escopo da escola | paginar |
| Distribuição/evolução | `snapshots_indicadores`/agregações | alternativa acessível |

## Estados

`default`, `hover` (linhas de tabela), `focus`, `loading` (por aba),
`empty` (aba sem dados), `error`, `success`, `access_denied` (aba/ação fora do
escopo). Escola inativa: hero esmaecido.

## Regras de negócio

- Todo o conteúdo é restrito à escola e ao escopo do ator.
- Abas carregam dados reais (não números fixos do protótipo).
- Ações de criação herdam a escola do contexto.

## Responsividade

KPI strip 5→3→2 colunas; grids de detalhe colapsam ≤900px; abas com rolagem
horizontal ≤560px. Sem overflow horizontal.

## Endpoints `/api/v2` necessários

- `GET /api/v2/escolas/{id}` — cabeçalho e dados gerais.
- `GET /api/v2/escolas/{id}/indicadores` — KPIs, desempenho, distribuição, evolução.
- `GET /api/v2/escolas/{id}/turmas|provas|alunos|equipe` — conteúdos das abas (paginados).

## Pendências/decisões

- Definir alternativa acessível para o sparkline e o donut (tabela/aria-label).
- Padronizar o carregamento por aba (lazy) para performance.
