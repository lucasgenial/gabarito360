# Tela: Criar Prova e Gabarito Oficial (`criar-prova.html`)

- **Rota web:** `/provas/criar` (e `/provas/{id}/editar` para rascunho)
- **Módulo:** Provas
- **Atores/permissões:** professor, coordenação (definir padrões), gestão escolar.
- **Objetivo:** criar a prova, marcar o gabarito oficial que o OMR usará e
  configurar cartão/turmas, em um fluxo de 3 passos.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** título + subtítulo; botões **"Salvar rascunho"** e
  **"Publicar gabarito"**.
- **Stepper (3 passos):** 1) Dados · 2) Gabarito oficial · 3) Cartão & turmas.
- **Painel de dados (sticky):**
  - Título da prova; Disciplina; Série/ano; Nº de questões (cada questão A–E).
  - **Padrões desta prova** (expansível): Alternativas por questão (3/4/5),
    Nota máxima, Tipo de pontuação (pesos iguais/personalizados), Escola;
    checkboxes "Anular questão se todas marcadas" e "Gerar cartão-resposta em PDF".
  - **Gabarito preenchido**: contador (preenchidas/total) + barra de progresso.
- **Editor de bolhas:** folha de respostas oficial — grade de questões, cada uma
  com bolhas A–E; clique marca a resposta correta.

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra/Validação |
|---|---|---|---|---|
| Título/Disciplina/Série | input/select | dados da prova | `POST/PUT /api/v2/provas` | título obrigatório |
| Nº de questões | number | gera a grade | (cliente) | regenera bolhas |
| Padrões (alternativas, nota, pontuação, escola) | select/number | configuração | `padroes_prova` | herda padrões do sistema |
| Anular se todas marcadas | checkbox | regra de correção | idem | política de anulação |
| Gerar cartão PDF | checkbox | material | `materiais_prova` | gera cartão |
| Bolha A–E | clique | define resposta correta | `gabaritos_respostas` | 1 resposta por questão |
| Salvar rascunho | botão | persiste rascunho | `POST /api/v2/provas` (status rascunho) | parcial permitido |
| Publicar gabarito | botão | publica | `POST /api/v2/provas/{id}/publicar` | exige gabarito completo |

## Dados exibidos / capturados

| Campo | Origem/Destino | Observação |
|---|---|---|
| Dados da prova | `provas` | — |
| Padrões | `padroes_prova` | herdados de config do sistema |
| Gabarito | `gabaritos_oficiais`, `gabaritos_respostas` | 1 resposta/questão |
| Cartão PDF | `materiais_prova` | gerado |

## Estados

`default`, `hover`/`focus`, `q-row[data-answer]` (questão respondida), `loading`
(salvar/publicar), `success`, `error`, `disabled` (publicar enquanto incompleto),
`access_denied`. Progresso atualiza ao marcar bolhas.

## Regras de negócio

- Cada questão tem 1 resposta correta; nº de alternativas conforme padrão (3/4/5).
- Resposta oficial única por gabarito e questão; questão única por prova/ordem.
- Publicar exige gabarito completo; o gabarito publicado é o padrão de correção do OMR.
- Política de anulação (todas marcadas) e pontuação (pesos) configuráveis.

## Responsividade

`editor-grid` 2→1 coluna ≤900px (painel deixa de ser sticky); `q-grid` 1 coluna.

## Endpoints `/api/v2` necessários

- `POST /api/v2/provas` / `PUT /api/v2/provas/{id}` — dados + rascunho.
- `PUT /api/v2/provas/{id}/gabarito` — respostas oficiais.
- `POST /api/v2/provas/{id}/publicar` — publicar gabarito.
- `GET /api/v2/padroes-prova` — padrões herdados.
- `POST /api/v2/provas/{id}/cartao.pdf` — gerar cartão.
- `GET /api/v2/provas/{id}/turmas` — passo 3 (vínculo a turmas).

## Pendências/decisões

- Detalhar o passo 3 (Cartão & turmas): vínculo a turmas e geração de aplicação.
- Confirmar pesos personalizados por questão e regra de anulação por marcação.
