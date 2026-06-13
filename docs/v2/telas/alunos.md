# Telas: Alunos — cadastrar, detalhe e editar (`aluno-cadastrar*.html`, `aluno-detalhe.html`, `aluno-editar.html`)

- **Rotas web:** `/alunos/novo` (com `?turma=`), `/alunos/{id}`, `/alunos/{id}/editar`
- **Módulo:** Alunos
- **Atores/permissões:** gestão escolar, coordenação, professor (suas turmas).
  Escopo obrigatório.
- **Objetivo:** matricular, visualizar e manter alunos, com foto, dados,
  responsável, turma, status, histórico de avaliações e ficha PDF.
- **Shell:** ver [`_shell.md`](_shell.md). As variantes de cadastro
  (`aluno-cadastrar.html` e `aluno-cadastrar-redesign.html`) consolidam a **união**
  de capacidades; a redesign orienta a composição visual.

## Cadastrar (`aluno-cadastrar-redesign.html` + `aluno-cadastrar.html`)

Layout 2 colunas: **card de identidade** (foto/preview + nome/turma) e
**formulário**:

- Foto do aluno (upload JPG/PNG até 2MB, preview).
- Nome completo* ; Nº de Matrícula* ; Data de Nascimento* ; Turma de Destino*
  (select, pré-selecionada via `?turma=`); Nome do Responsável; CPF do aluno
  (opcional); Gênero.
- Ações: Cancelar / Salvar Cadastro (volta ao detalhe da turma).

## Detalhe (`aluno-detalhe.html`)

- **Header de perfil:** avatar (iniciais), nome, série·turma, lista (Matrícula,
  Data Nasc.+idade, Responsável); ações **Editar dados** e **Ficha do Aluno (PDF)**.
- **KPIs (3):** Média Geral, Frequência, Provas Realizadas.
- **Histórico de Avaliações:** filtro por bimestre + tabela (Prova, Data,
  Desempenho [mini-bar], Nota, ação **Ver Resultado** → `/resultados/{...}`).
- **Evolução de Notas:** gráfico de barras por mês (alternativa acessível).

## Editar (`aluno-editar.html`)

Mesmo layout do cadastro, pré-preenchido, com:
- Nome*, Matrícula*, Data de Nascimento, **Turma Atual** (permite transferência),
  Nome do Responsável, **Status da Matrícula** (Ativo / Inativo·Trancado /
  Transferido).
- Ações: **Excluir Registro** (confirmação) · Cancelar · Salvar Alterações.

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra/Validação |
|---|---|---|---|---|
| Foto | file | upload/preview | `POST /api/v2/alunos/{id}/foto` | JPG/PNG ≤2MB |
| Nome completo* | input | identificação | `POST/PUT alunos` | required |
| Nº de Matrícula* | input | identificação | idem | única por escola/rede |
| Data de Nascimento | date | dados | idem | calcula idade no detalhe |
| Turma (destino/atual) | select | matrícula/transferência | idem | gera/atualiza `matriculas_turmas` |
| Responsável | input | vínculo | idem | cria/associa `responsaveis` |
| CPF do aluno | input | identificação | idem | opcional |
| Gênero | select | dados | idem | opcional |
| Status da Matrícula | select | Ativo/Inativo/Transferido | `PUT alunos/{id}` | controla acesso/listagens |
| Salvar (cadastro/alterações) | botão | persiste | `POST/PUT /api/v2/alunos` | valida obrigatórios |
| Excluir Registro | botão | remoção | `DELETE`/solicitação LGPD | confirmação; inativar/anonimizar |
| Editar dados (detalhe) | link | abre edição | `/alunos/{id}/editar` | permissão |
| Ficha do Aluno (PDF) | botão | exporta ficha | `GET /api/v2/alunos/{id}/ficha.pdf` | exportação autorizada/auditada |
| Filtro de bimestre | select | filtra histórico | `GET /api/v2/alunos/{id}/avaliacoes?periodo=` | — |
| Ver Resultado | link | resultado individual | `/resultados/{...}` | — |

## Dados exibidos / capturados

| Campo | Origem/Destino | Observação |
|---|---|---|
| Identidade (nome, matrícula, nasc., responsável, foto) | `alunos`, `responsaveis`, `alunos_responsaveis`, `arquivos` | — |
| Turma/Status | `matriculas_turmas` | transferência = nova matrícula |
| KPIs (média, frequência, provas) | agregações de `resultados`, `frequencias` | reais |
| Histórico de avaliações | `resultados`/`resultados_questoes` | por bimestre |
| Evolução de notas | série temporal de resultados | alternativa acessível |

## Estados

`default`, `focus`, `invalid` (campos obrigatórios), `disabled`, `loading`
(salvar/PDF), `success` (toast/redireciona), `error`, `empty` (sem avaliações),
`access_denied`. Aluno pendente: desempenho "—".

## Regras de negócio

- Matrícula única por escola/rede; um cartão válido por aluno/prova (regra de OMR).
- Transferência de turma preserva histórico (nova matrícula, não sobrescreve).
- "Excluir Registro" segue adaptação segura LGPD (inativação/anonimização ou
  solicitação rastreável) — não exclusão direta de dados pessoais.
- Status da matrícula (Ativo/Inativo/Transferido) controla listagens e acesso.
- Ficha PDF e exportações são autorizadas e auditadas.

## Responsividade

Form 2→1 coluna ≤850px (identidade centralizada); `form-grid` 1 coluna ≤600px;
KPIs do detalhe 1 coluna ≤768px; rótulos de ação ocultos ≤900px. Sem overflow.

## Endpoints `/api/v2` necessários

- `GET /api/v2/alunos/{id}` — detalhe e KPIs.
- `POST /api/v2/alunos` / `PUT /api/v2/alunos/{id}` — cadastrar/editar.
- `POST /api/v2/alunos/{id}/foto` — upload de foto.
- `GET /api/v2/alunos/{id}/avaliacoes?periodo=` — histórico.
- `GET /api/v2/alunos/{id}/evolucao` — série de notas (payload acessível).
- `GET /api/v2/alunos/{id}/ficha.pdf` — ficha PDF.
- `POST /api/v2/solicitacoes-lgpd` — remoção/anonimização rastreável.
- `GET /api/v2/turmas?escola=` — opções de turma.

## Pendências/decisões

- Definir geração da matrícula (manual vs automática por ano/escola).
- Confirmar modelo de responsável (1:N e contatos) e consentimento LGPD do aluno.
- Padronizar "Excluir Registro" como solicitação LGPD (recomendado).
