# Tela: Turmas (lista) (`turmas.html`)

- **Rota web:** `/turmas`
- **Módulo:** Turmas
- **Atores/permissões:** gestão escolar, coordenação, professor (suas turmas).
  Escopo organizacional obrigatório.
- **Objetivo:** listar, buscar, filtrar, importar e criar turmas, com indicadores
  de desempenho e status.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** título "Turmas" + subtítulo com contagem (`N turmas · M alunos`);
  botões **"Importar planilha"** e **"+ Nova turma"**.
- **Toolbar:** busca por nome + filtro de **série** + filtro de **status**
  (Em dia / Em recuperação / Com pendências).
- **Tabela:** Turma (avatar + nome), Série (badge), Alunos, **Desempenho médio**
  (mini-bar + %), Status (badge colorido), ação **Ver turma**.
- **Empty state:** "Nenhuma turma corresponde aos filtros."

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra/Validação |
|---|---|---|---|---|
| Buscar turma | input | filtra por nome | `GET /api/v2/turmas?q=` | busca real no escopo |
| Filtro série | select | filtra por série | `GET /api/v2/turmas?serie=` | — |
| Filtro status | select | em-dia/recuperação/pendência | `GET /api/v2/turmas?status=` | status derivado |
| Importar planilha | botão | importação em lote | `POST /api/v2/turmas/importar` | idempotente; valida/relata erros |
| + Nova turma | botão | cria turma | `POST /api/v2/turmas` | escola/período/código únicos |
| Ver turma | link | detalhe | `/turmas/{id}` | — |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Contagem (turmas/alunos) | agregações de `turmas`, `matriculas_turmas` | no escopo |
| Linha (nome, série, alunos, média, status) | `turmas` + agregações | status: pendência > recuperação > em dia |

## Estados

`default`, `hover`, `focus`, `loading`, `empty` (sem resultados), `error`,
`success` (importação/criação), `access_denied` (fora do escopo).

## Regras de negócio

- Status da turma é **derivado**: com pendências (danger) tem precedência sobre
  em recuperação (warn), senão "em dia" (success).
- Turma única por escola, período letivo e código.
- Importação por planilha é idempotente e reporta erros por linha.
- Lista restrita ao escopo (professor vê apenas suas turmas).

## Responsividade

Toolbar quebra (`flex-wrap`); ações da tabela ocultam o rótulo (`btn-label`)
≤900px. Sem rolagem horizontal indevida.

## Endpoints `/api/v2` necessários

- `GET /api/v2/turmas?q=&serie=&status=&page=` — lista filtrada por escopo.
- `POST /api/v2/turmas` — criar.
- `POST /api/v2/turmas/importar` — importação por planilha (idempotente).
- `GET /api/v2/turmas/kpis` — contagens do cabeçalho.

## Pendências/decisões

- Definir o formato/colunas da planilha de importação e o relatório de erros.
- Confirmar a regra exata de derivação do status (limiares de média/pendência).
