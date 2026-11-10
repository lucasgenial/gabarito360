# 055 — Acompanhamento pedagógico — Casos de uso

**Natureza:** nova (só o visual demonstrativo existe)
**Perfis envolvidos:** Professor, Coordenador, equipe pedagógica, Direção e Vice-direção; permissões novas a definir. Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Casos propostos a partir do docs/177 (`docs/177-plano-paineis-turmas.md`) (P6). Nenhum está implementado como dado real; serão detalhados antes da execução, depois de FN-056 e FN-039.

---

## UC-055-01 — Ver os modelos de acompanhamento nos painéis (atual)

**Ator:** perfis com acesso aos painéis (FN-053)
**Permissão:** `turma.ver`
**Escopo:** painéis de Turmas e da Turma
**Pré-condições:** painel aberto.
**Pós-condições:** nenhuma.
**Tela/rota:** web `turmas.index`, `turmas.show`

**Fluxo principal:**
1. Os cards de habilidades, intervenções, reavaliações, metas, conselho e devolutivas aparecem como "Em breve" com "Dados demonstrativos".

**Critérios de aceite:**
- [x] Nenhum dado demonstrativo é apresentado como real nem entra em PDF.

**Situação:** Parcial — apenas visual. Evidência: `config/paineis.php`, PR #211–#214

---

## UC-055-02 — Cadastrar habilidades e associá-las aos itens

**Ator:** Coordenador, Professor
**Permissão:** a definir
**Escopo:** escola ou espaço pessoal
**Pré-condições:** provas com gabarito.
**Pós-condições:** habilidades versionadas e itens associados com proveniência.

**Fluxo principal:**
1. O usuário cadastra habilidades e descritores.
2. Associa itens das provas às habilidades, com revisão.
3. Registra as habilidades planejadas por turma, disciplina e período.

**Critérios de aceite:**
- [ ] Área do conhecimento não é tratada como habilidade.
- [ ] Mapas habilidade × turma só aparecem com evidência mínima.

**Situação:** Não implementado — subfase 1.1 (P6.1)

---

## UC-055-03 — Registrar e acompanhar uma intervenção

**Ator:** Professor, Coordenador, equipe pedagógica
**Permissão:** a definir
**Escopo:** turma no escopo
**Pré-condições:** evidência de avaliação; módulo de agenda (FN-056).
**Pós-condições:** intervenção com responsável, estratégia, prazo e situação; compromisso na agenda única.

**Fluxo principal:**
1. A partir de um grupo do gráfico, o sistema propõe um acompanhamento; o usuário revisa.
2. Registra necessidade, evidências, estudantes, responsável, estratégia e prazo.
3. Depois da ação, registra o que foi feito e, separadamente, o resultado observado.

**Fluxos alternativos e de erro:**
- 3a. Reavaliação: a avaliação original é preservada; a regra de composição da nota é definida no planejamento.

**Critérios de aceite:**
- [ ] Nenhum segundo calendário é criado.
- [ ] A indicação de recuperação do painel não gera intervenção automaticamente.

**Situação:** Não implementado — subfase 1.2 (P6.2)

---

## UC-055-04 — Analisar cruzamentos e evolução

**Ator:** Coordenador, Direção, Professor
**Permissão:** a definir
**Escopo:** turmas no escopo
**Pré-condições:** períodos letivos (FN-039) e atribuições válidas.
**Pós-condições:** nenhuma.

**Fluxo principal:**
1. O usuário consulta participação × resultado, turma × disciplina, habilidades planejadas × avaliadas e intervenção × reavaliação.
2. Em comparações antes/depois, o sistema usa os mesmos estudantes e mostra a base e as perdas de acompanhamento.

**Critérios de aceite:**
- [ ] O sistema não afirma que a intervenção causou a mudança observada.
- [ ] Nenhum ranking é apresentado como medida de eficácia docente.

**Situação:** Não implementado — subfase 1.3 (P6.3)

---

## UC-055-05 — Preparar o conselho e acompanhar metas

**Ator:** Coordenador, Direção, Vice-direção
**Permissão:** a definir
**Escopo:** escola
**Pré-condições:** resultados e intervenções registrados.
**Pós-condições:** decisões com responsáveis e prazos; metas com situação inicial, critério, período e responsável.

**Fluxo principal:**
1. O sistema monta a visão do conselho com avanços, dificuldades, pendências e encaminhamentos.
2. O usuário registra decisões e define metas.

**Critérios de aceite:**
- [ ] Observações reservadas e detalhamento nominal só para pessoas autorizadas.

**Situação:** Não implementado — subfase 1.4 (P6.4)
