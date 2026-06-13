# Tela: Provas e Gabaritos (lista) (`provas.html`)

- **Rota web:** `/provas`
- **Módulo:** Provas
- **Atores/permissões:** professor (suas provas), coordenação, gestão escolar.
  Escopo obrigatório.
- **Objetivo:** listar, buscar e filtrar provas, com status do ciclo (rascunho →
  publicada → em correção → corrigida) e ação contextual por status.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** título + subtítulo com contagem/período; botão **"+ Nova prova"**.
- **Toolbar:** busca por título + filtro **disciplina** + filtro **status**.
- **Tabela:** Prova (título + disciplina), Turmas, Aplicação, Status (badge),
  **Progresso** (varia por status), ação contextual.
- **Empty state:** "Nenhuma prova corresponde aos filtros."

### Progresso e ação por status

| Status | Progresso exibido | Ação |
|---|---|---|
| Rascunho | questões no gabarito (g/total) | **Continuar** → `/provas/{id}/editar` |
| Publicada | "Aguardando aplicação" | **Ver gabarito** → `/provas/{id}/gabarito` |
| Em correção | cartões lidos (done/total) | **Acompanhar** → `/correcao/{id}` |
| Corrigida | média + cartões corrigidos | **Ver resultados** → `/relatorios/prova/{id}` |

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra |
|---|---|---|---|---|
| Buscar | input | filtra por título | `GET /api/v2/provas?q=` | escopo |
| Filtro disciplina | select | filtra | `?disciplina=` | — |
| Filtro status | select | filtra | `?status=` | — |
| + Nova prova | botão | criação | `/provas/criar` | permissão |
| Ação contextual | link | conforme status | rotas acima | depende do status |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Lista (título, disciplina, turmas, data, status) | `provas`, `provas_turmas` | escopo |
| Progresso | `gabaritos_oficiais`, `leituras`, `resultados` | conforme status |

## Estados

`default`, `hover`, `focus`, `loading`, `empty`, `error`, `success`,
`access_denied`. Status com badge: muted/info/warn/success.

## Regras de negócio

- Ciclo de status: rascunho → publicada → em correção → corrigida.
- Provas restritas ao escopo (professor vê as suas).
- Progresso reflete a fase: questões do gabarito, cartões lidos ou média final.

## Responsividade

Toolbar `flex-wrap`; coluna Aplicação e rótulos de ação ocultos ≤900px.

## Endpoints `/api/v2` necessários

- `GET /api/v2/provas?q=&disciplina=&status=&page=`
- `GET /api/v2/provas/kpis`

## Pendências/decisões

- Confirmar transições de status permitidas e quem pode publicar/corrigir.
