# 027 — Professor independente (PA1) — Casos de uso

**Natureza:** evolução de existente (um caso parcial; demais propostos)
**Perfis envolvidos:** `professor_autonomo` (proprietário do espaço pessoal); representantes da escola na integração. Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

---

## UC-027-01 — Organizar turmas pessoais pelas instituições declaradas

**Ator:** professor autônomo
**Permissão:** `turma.gerir`; `catalogo.consultar` para a referência
**Escopo:** espaço pessoal
**Pré-condições:** referência pessoal ativa em "Minhas instituições" (UC-031-02).
**Pós-condições:** turma pessoal associada a uma referência; listas agrupadas por instituição.
**Tela/rota:** formulário de turma pessoal (a evoluir); hoje só `/app/minhas-instituicoes`.

**Fluxo principal (proposto):**
1. Ao criar ou editar a turma, o professor escolhe uma das suas referências.
2. Turmas e disciplinas aparecem agrupadas por essa referência.

**Fluxos alternativos e de erro:**
- 1a. Referência arquivada → não é oferecida a turmas novas; as antigas mantêm o histórico.

**Critérios de aceite:**
- [ ] A referência não cria escola, vínculo institucional nem acesso escolar.
- [ ] Turmas existentes não são associadas automaticamente.

**Situação:** Parcial — a seleção de referências existe (FN-031, PR #157); o uso em turmas/disciplinas não tem código. Subfase 1.1 (PA1-A).

---

## UC-027-02 — Informar CPF opcional do aluno pessoal

**Ator:** professor autônomo
**Permissão:** `aluno.gerir`
**Escopo:** espaço pessoal

**Fluxo principal (proposto):** informar CPF opcional; o sistema valida, cifra e indexa por HMAC (FN-016); os demais campos continuam mínimos.

**Critérios de aceite:**
- [ ] CPF nunca em URL, log ou token; ausência de CPF não impede o cadastro.

**Situação:** Não implementado — ⚠ hoje o CPF é **proibido** por validação e trigger (UC-022-03). Subfase 1.2 (PA1-B), após a revisão formal de T3.

---

## UC-027-03 — Integrar dados pessoais a uma escola

**Ator:** professor autônomo (seu lado); representante da escola (recebimento)
**Permissão:** conforme a matriz TR1 (FN-025)
**Escopo:** espaço pessoal → escola
**Pré-condições:** convite institucional aceito; alunos identificados por CPF conciliado ou revisão manual de identidade provisória.

**Fluxo principal (proposto):**
1. O professor solicita a integração e seleciona o que oferece.
2. A escola compara o permitido e decide reutilizar o cadastro local ou criar/completar.
3. A execução preserva a proveniência dos resultados do professor.

**Fluxos alternativos e de erro:**
- 2a. Conflito de identidade → conciliação com recibo e rollback por unidade; não sobrescreve o aluno da escola.

**Critérios de aceite:**
- [ ] Nenhuma fusão por nome ou matrícula.
- [ ] Nota pessoal não vira nota oficial sem decisão explícita.

**Situação:** Não implementado — subfase 1.3 (PA1-C). O fluxo de cópias de T5 (FN-024) cobre parte disso com a política antiga.

---

## UC-027-04 — Manter histórico admitido ao sair da escola

**Ator:** professor com vínculo escolar em encerramento; gestão da escola
**Permissão:** conforme a matriz TR1 (FN-025)
**Escopo:** escola → espaço pessoal

**Fluxo principal (proposto):**
1. Antes do encerramento, o sistema mostra a prévia do que ficará e do que não ficará com o professor.
2. A escola autoriza o histórico mínimo das turmas, disciplinas e períodos de atuação dele.
3. O vínculo é encerrado; conta, e-mail e outros vínculos permanecem.

**Fluxos alternativos e de erro:**
- 3a. Vínculo já encerrado → não serve para exportação retroativa.

**Critérios de aceite:**
- [ ] Fatos futuros e dados de outros professores ficam indisponíveis.

**Situação:** Não implementado — subfase 1.4 (PA1-D). Existem `ContinuidadeCopia` e `EncerrarVinculoProfessor` sem rota (T5-D1).

---

## UC-027-05 — Ver o perfil apresentado como "Professor independente"

**Ator:** professor autônomo
**Fluxo (proposto):** a interface e os documentos usam o rótulo "Professor independente"; o identificador técnico não muda.

**Situação:** Não implementado — hoje o rótulo é "Professor autônomo" e os documentos trazem "Uso autônomo". Decisão em §8 do [planejamento](planejamento.md).
