# 020 — Acesso próprio do aluno — Casos de uso

**Natureza:** evolução de existente (código parcial, sem liberação)
**Perfis envolvidos:** Aluno; gestores que ligam a conta ao cadastro (a definir) — docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Casos de alto nível; o detalhamento depende da decisão §4 item 10 do plano mestre.

Referências: UC-005 "Visualizar Resultado Individual do Aluno" e UC-007 "Monitorar Desempenho (Dashboard por Perfil)" de docs/07 (`docs/07-casos-de-uso.md`).

---

## UC-020-01 — Ligar a conta ao cadastro do aluno

**Ator:** gestor da escola (perfil a definir)
**Permissão:** a definir (hoje `usuario.gerir` cria a conta de perfil `aluno`)
**Escopo:** escola
**Pré-condições:** decisão de liberar o login; aluno cadastrado na escola.
**Pós-condições:** vínculo de perfil `aluno` com referência ao cadastro em `vinculo_alunos`.
**Tela/rota:** a definir

**Fluxo principal (proposto):**
1. O gestor escolhe o aluno e informa os dados da conta.
2. O sistema cria o vínculo e a referência ao cadastro, na mesma escola e no mesmo espaço.
3. O convite segue o fluxo de FN-015 (ou FN-017).

**Fluxos alternativos e de erro:**
- ⚠ 2a. Hoje o cadastro de membro com perfil `aluno` cria conta e vínculo, mas não a referência; só `g360:conciliar-vinculos` a grava (subfase 1.2).

**Critérios de aceite:**
- [ ] Uma conta nunca fica ligada a aluno de outra escola.
- [ ] Regras de menoridade e consentimento definidas antes do primeiro convite.

**Situação:** Não implementado — evidência: `UsuarioController::store`, `ConciliarVinculos`.

---

## UC-020-02 — Ver o painel do aluno

**Ator:** Aluno
**Permissão:** rota com `perfil:aluno`
**Escopo:** o próprio cadastro
**Pré-condições:** vínculo de aluno selecionado, com referência ao cadastro.
**Pós-condições:** nenhuma.
**Tela/rota:** `mockups/dashboard-aluno.html`; web `GET /app/painel` (visão `dashboard/aluno`); API `GET /api/v1/dashboard/aluno`

**Fluxo principal (comportamento atual do código):**
1. A API resolve o aluno pelas referências do vínculo.
2. Mostra nome, turma e escola, provas realizadas, média e melhor disciplina.

**Fluxos alternativos e de erro:**
- 1a. Mais de uma referência: "Selecione o contexto da consulta." com as opções.
- 1b. Sem referência: "Aluno não encontrado."

**Critérios de aceite:**
- [ ] Nenhum dado de outro aluno aparece.
- [ ] ⚠ Uso liberado só depois da decisão (subfase 1.1).

**Situação:** Parcial — código presente, sem liberação nem caminho de gestão para criar a referência — evidência: `DashboardController::aluno`, `ContextoVinculo` (referências de aluno).

---

## UC-020-03 — Ver "Minhas provas" e o próprio resultado

**Ator:** Aluno
**Permissão:** `resultado.ver` (padrão do perfil)
**Escopo:** o próprio cadastro
**Pré-condições:** UC-020-02.
**Pós-condições:** nenhuma.
**Tela/rota:** `mockups/dashboard-aluno.html` → `resultado.html`

**Fluxo principal (proposto):**
1. O aluno vê a lista das provas que fez.
2. Abre o resultado com a folha corrigida, somente a sua.

**Critérios de aceite:**
- [ ] Trocar a referência na requisição não abre o resultado de outro aluno.
- [ ] Conferência por perfil registrada por texto.

**Situação:** Não implementado como fluxo conferido — evidência: docs/19 MP-041; subfase 1.3.

---

## UC-020-04 — Decidir a liberação do login do aluno

**Ator:** responsável
**Permissão:** —
**Escopo:** plataforma
**Pré-condições:** —
**Pós-condições:** decisão registrada no plano mestre; funcionalidade priorizada ou adiada.
**Tela/rota:** —

**Fluxo principal:** decidir junto com o portal dos responsáveis (FN-067), conforme a recomendação do §4 item 10.

**Critérios de aceite:**
- [ ] Decisão e data registradas.

**Situação:** Não implementado — subfase 1.1.
