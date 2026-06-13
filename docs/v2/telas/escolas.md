# Tela: Escolas (lista) (`escolas.html`)

- **Rota web:** `/escolas`
- **Módulo:** Escolas
- **Atores/permissões:** admin geral, gestor de núcleo (suas escolas), gestão
  escolar (sua escola). Escopo organizacional obrigatório.
- **Objetivo:** listar, buscar, cadastrar, editar, reativar e abrir o detalhe das
  unidades escolares.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** título "Escolas" + subtítulo; à direita **busca** (input
  `search`) e botão **"Nova escola"** (abre modal).
- **Faixa de KPIs** (4 cartões): Escolas cadastradas, Escolas ativas, Alunos
  totais, Turmas ativas.
- **Grid de cartões** (`auto-fill, minmax(340px,1fr)`), um por escola:
  ícone, nome, código INEP, **badge de status** (Ativa/Inativa), metadados
  (endereço, telefone, e-mail, diretor(a)), rodapé com **estatísticas** (Alunos,
  Turmas, Provas) e ações (**Editar**, **Ver mais**; escola inativa adiciona
  **Reativar**).
- **Modal Cadastrar/Editar escola** (`#modal-escola`, `role=dialog`): seções
  Identificação, Endereço, Contato, Status, com rodapé Cancelar/Salvar.
- **Empty state** previsto (ícone + título) quando não há escolas.

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra/Validação |
|---|---|---|---|---|
| Buscar escola | input search | filtra por nome (cliente no mockup) | `GET /api/v2/escolas?q=` | busca real no escopo |
| Nova escola | botão | abre modal de cadastro | — | foco no primeiro campo |
| Editar | botão | abre modal pré-preenchido | `GET /api/v2/escolas/{id}` | escopo/permissão |
| Ver mais | link | abre detalhe | `/escolas/{id}` | — |
| Reativar | botão | reativa escola inativa | `POST /api/v2/escolas/{id}/reativar` | apenas inativas; auditado |
| Nome da escola* | input | obrigatório | `POST/PUT /api/v2/escolas` | required; valida vazio |
| Código INEP | input (8) | identificação | idem | único quando informado |
| Tipo de rede | select | Estadual/Municipal/Federal/Privada | idem | — |
| Logradouro/Cidade/UF | inputs/select | endereço | idem | — |
| Telefone/E-mail/Diretor(a) | inputs | contato | idem | e-mail válido |
| Escola ativa | checkbox | disponibilidade | idem | controla aparição em provas/turmas |
| Salvar escola | botão | persiste | `POST/PUT /api/v2/escolas` | toast de sucesso |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| KPIs (cadastradas/ativas/alunos/turmas) | agregações de `escolas`, `alunos`, `turmas` | consulta real no escopo |
| Cartão (nome, INEP, status, endereço, contato, diretor) | `escolas` | — |
| Estatísticas (alunos/turmas/provas) | agregações por escola | — |
| Motivo/inatividade | `escolas.status`/auditoria | "Inativa desde …" |

## Estados

`default`, `hover` (cartão eleva), `focus`, `loading` (carga da lista),
`empty` (sem escolas), `error`, `success` (toast ao salvar/reativar),
`disabled` (escola inativa esmaecida), `access_denied` (fora do escopo).

## Regras de negócio

- Escola única por núcleo; INEP único quando informado.
- "Inativar" no lugar de excluir; **Reativar** disponível para inativas
  (adaptação segura — sem exclusão direta).
- Status "ativa" controla a aparição da escola na seleção de provas/turmas.
- Listagem e ações restritas ao escopo do ator.

## Responsividade

Grid colapsa para 1 coluna ≤700px; `form-row-2` do modal vira 1 coluna; modal
com `max-height:90vh` e rolagem. Sem rolagem horizontal nos 9 viewports.

## Endpoints `/api/v2` necessários

- `GET /api/v2/escolas?q=&status=&page=` — lista paginada por escopo.
- `GET /api/v2/escolas/{id}` — dados para edição.
- `POST /api/v2/escolas` / `PUT /api/v2/escolas/{id}` — criar/editar.
- `POST /api/v2/escolas/{id}/reativar` — reativar.
- `GET /api/v2/escolas/kpis` — indicadores da faixa.

## Pendências/decisões

- Confirmar campos do INEP (8 dígitos) e máscara/validação.
- Definir paginação/scroll infinito do grid para redes grandes.
