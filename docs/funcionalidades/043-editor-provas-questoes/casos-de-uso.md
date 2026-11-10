# 043 — Editor de provas e banco de questões — Casos de uso

**Natureza:** nova (candidata; casos de uso propostos de alto nível)
**Perfis envolvidos:** Professor, Coordenador, Professor autônomo (autoria); gestão da escola (consulta) — a confirmar no [docs/03](../../03-perfis-e-permissoes.md)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Todos estão **Não implementados**; hoje a prova tem só configuração e gabarito ([FN-040](../040-provas-gabaritos/casos-de-uso.md)).

---

## UC-043-01 — Manter questões no banco

**Ator:** Professor, Coordenador
**Permissão:** a definir
**Escopo:** escola | espaço pessoal
**Pré-condições:** —
**Pós-condições:** questão com enunciado, alternativas, resposta e classificação (disciplina, tema) gravada como entidade própria.
**Tela/rota:** a definir

**Fluxo principal:**
1. O ator cria a questão e a classifica.
2. Pode editar, arquivar e buscar questões do próprio alcance.

**Critérios de aceite:**
- [ ] Questões ficam em tabelas próprias, separadas do gabarito.
- [ ] Isolamento por espaço e regras de autoria.

**Situação:** Não implementado — origem: docs/02 fase 2.

---

## UC-043-02 — Montar a prova a partir do banco

**Ator:** Professor, Coordenador
**Permissão:** a definir (base `prova.criar`)
**Escopo:** escola | espaço pessoal
**Pré-condições:** questões disponíveis.
**Pós-condições:** prova composta por questões ordenadas; gabarito derivado da composição.
**Tela/rota:** a definir

**Fluxo principal:**
1. O ator seleciona e ordena questões.
2. O sistema gera o gabarito e o documento da prova.

**Critérios de aceite:**
- [ ] Alterar a questão no banco não altera provas já aplicadas.

**Situação:** Não implementado — origem: docs/02 fase 3.

---

## UC-043-03 — Gerar versões da prova

**Ator:** Professor, Coordenador
**Permissão:** a definir
**Escopo:** escola | espaço pessoal
**Pré-condições:** prova montada.
**Pós-condições:** versões distintas com gabarito por versão; versão aplicada a cada aluno rastreada.
**Tela/rota:** a definir

**Critérios de aceite:**
- [ ] A correção usa o gabarito da versão do cartão do aluno.

**Situação:** Não implementado — origem: docs/02 fase 4; docs/12 (`ProvaVersao`).

---

## UC-043-04 — Usar questões discursivas e outros formatos

**Ator:** Professor, Coordenador
**Permissão:** a definir
**Escopo:** escola | espaço pessoal
**Pré-condições:** novo valor de `formato_avaliacao` aprovado.
**Pós-condições:** prova com discursivas, em formato impresso, digital ou misto, e marcações na própria página.
**Tela/rota:** a definir

**Critérios de aceite:**
- [ ] Provas no formato `gabarito` não são convertidas.

**Situação:** Não implementado — origem: docs/02 fase 5; docs/118; docs/164.

---

## UC-043-05 — Consultar o relatório por tema

**Ator:** perfis com acesso a relatórios
**Permissão:** `relatorio.prova` (a confirmar)
**Escopo:** turma | escola
**Pré-condições:** prova composta por questões classificadas.
**Pós-condições:** nenhuma.
**Tela/rota:** `resultado.html` (parte marcada como parcial no docs/19)

**Critérios de aceite:**
- [ ] Acertos por tema calculados a partir da classificação das questões.

**Situação:** Não implementado — origem: docs/06 (pós-MVP); pendência de FN-049.
