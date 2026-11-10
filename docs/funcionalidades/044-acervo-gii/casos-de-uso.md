# 044 — Acervo inteligente (GII) — Casos de uso

**Natureza:** nova (candidata; casos de uso propostos de alto nível)
**Perfis envolvidos:** Professor, Coordenador, Professor autônomo — a confirmar no docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Todos estão **Não implementados**; não existe motor nem acervo no código.

---

## UC-044-01 — Consultar o acervo de itens

**Ator:** Professor, Coordenador
**Permissão:** a definir
**Escopo:** a definir (acervo comum, consultado a partir do vínculo ativo)
**Pré-condições:** acervo carregado, com início pelo ENEM.
**Pós-condições:** nenhuma.
**Tela/rota:** a definir

**Fluxo principal:**
1. O ator busca itens por área, disciplina e classificação.
2. Abre o item e vê enunciado, resposta e classificação.

**Critérios de aceite:**
- [ ] Origem e direito de uso de cada item identificados.

**Situação:** Não implementado — origem: docs/164; docs/177 P7.1.2.

---

## UC-044-02 — Receber sugestões de itens para uma necessidade

**Ator:** Professor, Coordenador
**Permissão:** a definir
**Escopo:** turma
**Pré-condições:** necessidade pedagógica selecionada (por exemplo, habilidade com baixo desempenho).
**Pós-condições:** itens escolhidos pelo professor ficam disponíveis para a elaboração da prova (FN-043).
**Tela/rota:** a definir

**Fluxo principal:**
1. O ator seleciona a necessidade.
2. O sistema sugere itens.
3. O ator revisa e aceita ou descarta cada sugestão.

**Critérios de aceite:**
- [ ] Nenhuma sugestão entra em prova sem revisão do professor.

**Situação:** Não implementado — origem: docs/177 P7.1.4.

---

## UC-044-03 — Ver habilidades nos cards dos painéis

**Ator:** perfis com acesso aos painéis
**Permissão:** `relatorio.turma` (a confirmar)
**Escopo:** turma | escola
**Pré-condições:** provas compostas por itens classificados.
**Pós-condições:** nenhuma.
**Tela/rota:** cards de habilidades, planejamento e retomada dos painéis (FN-053)

**Fluxo principal:**
1. Os cards mostram o desempenho por habilidade a partir das classificações dos itens.

**Fluxos alternativos e de erro:**
- 1a. Sem integração real → o card permanece "Em breve".

**Critérios de aceite:**
- [ ] Indicador só aparece com fonte adequada e autorização no servidor.
- [ ] A classificação manual do acompanhamento (FN-055) não é bloqueada.

**Situação:** Não implementado — origem: docs/177 P7.1.3, P7.1.5.
