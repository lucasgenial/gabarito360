# Telas: Acompanhar Correção (`acompanhar-correcao.html`, `acompanhar-correcao-turma.html`)

- **Rotas web:** `/correcao/{prova}` (visão geral da prova) e
  `/correcao/{prova}/turma/{turma}` (recorte por turma)
- **Módulo:** Correção
- **Atores/permissões:** professor da prova/turma, coordenação, gestão. Escopo.
- **Objetivo:** acompanhar em tempo real a leitura/correção dos cartões, resolver
  pendências (ambíguas, em branco) e ver os cartões processados.
- **Shell:** ver [`_shell.md`](_shell.md). A versão **geral** agrega todas as
  turmas da prova; a versão **turma** recorta por uma turma.

## Layout e componentes

- **Cabeçalho:** título da prova + subtítulo (disciplina · turma · aplicação ·
  "leitura em andamento"); botão **"Atualizar leitura"** (recarrega snapshot).
- **Cartão de progresso:** **donut** (% lido) + **KPIs** (Cartões lidos,
  Pendentes, Ambíguos, Total) + barra de progresso.
- **Pendências de leitura:** lista de cartões com problema (ex.: "Questão 7 —
  marcações em B e D"; "Questão 14 — nenhuma marcação"), cada um com **botões de
  resolução** (escolher a alternativa ou "Marcar em branco").
- **Processados recentemente:** lista de cartões já corrigidos (aluno + nota + tempo).

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra |
|---|---|---|---|---|
| Atualizar leitura | botão | recarrega snapshot | `GET /api/v2/correcao/{prova}` + evento `application.progress.updated` | desabilita ao concluir |
| Resolver pendência | botão | confirma alternativa / em branco | `POST /api/v2/leituras/{leitura}/revisao` | idempotente; auditado |
| Lista de processados | leitura | feed recente | evento `reading.confirmed` | tempo real |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Donut/KPIs (lidos, pendentes, ambíguos, total) | `aplicacoes`, `leituras_cartao` | escopo prova/turma |
| Pendências (ambíguas/branco/baixa confiança) | `respostas_detectadas`, `processamentos_omr` | exige revisão |
| Processados | `resultados` recentes | feed |

## Estados

`default`, `loading`, `empty` ("Nenhuma pendência de revisão."), `error`,
`success` (pendência resolvida), `access_denied`, **concluído** ("Leitura
concluída", botão desabilitado). Tempo real via Reverb.

## Regras de negócio

- Baixa confiança, marcação ambígua ou em branco **exigem revisão explícita**
  antes de confirmar (não corrige automaticamente).
- Resolver uma pendência é **idempotente** e auditado; aumenta "lidos" e reduz
  "ambíguos".
- Eventos em tempo real: `application.progress.updated`, `reading.review.required`,
  `reading.confirmed`. Snapshot recarregável após reconexão.

## Responsividade

`progress-head` colapsa para 1 coluna em telas estreitas; listas full-width.

## Endpoints `/api/v2` necessários

- `GET /api/v2/correcao/{prova}` e `.../turma/{turma}` — snapshot de progresso.
- `GET /api/v2/correcao/{prova}/pendencias` — itens a revisar.
- `POST /api/v2/leituras/{leitura}/revisao` — resolver pendência (idempotente).
- Canais Reverb privados escopados para os eventos acima.

## Pendências/decisões

- Definir o contrato de "pendência" (tipos: ambígua, em branco, baixa confiança).
- Confirmar política de reprocessamento e preservação de histórico de leitura.
