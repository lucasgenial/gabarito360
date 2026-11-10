# 051 — TCT/TRI, integridade e sínteses — Casos de uso

**Natureza:** nova (Candidata; casos propostos de alto nível)
**Perfis envolvidos:** a definir no plano; o docs/177 pede permissão própria para integridade. Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Casos derivados do docs/177 (`docs/177-plano-paineis-turmas.md`) (P7.2–P7.4). Nenhum está implementado.

---

## UC-051-01 — Consultar medidas psicométricas de uma avaliação

**Ator:** a definir (educadores e gestão pedagógica)
**Permissão:** a definir
**Escopo:** prova e turmas no escopo
**Pré-condições:** itens e população suficientes para o método escolhido.
**Pós-condições:** nenhuma.

**Fluxo principal:**
1. O usuário abre a análise da prova.
2. O sistema mostra as medidas (TCT/TRI) separadas da nota e do percentual, com escala, método, versão e limites de interpretação.

**Critérios de aceite:**
- [ ] Comparação entre instrumentos só após validação específica.

**Situação:** Não implementado

---

## UC-051-02 — Revisar indícios de integridade

**Ator:** a definir (permissão própria)
**Permissão:** a definir
**Escopo:** a definir
**Pré-condições:** motor de integridade validado.
**Pós-condições:** indício confirmado ou descartado com registro do revisor.

**Fluxo principal:**
1. O sistema aponta padrões anômalos, similaridade ou distratores raros com as evidências.
2. Um revisor humano analisa e registra a conclusão.

**Critérios de aceite:**
- [ ] Nenhuma conclusão automática sem revisão humana.
- [ ] Acesso restrito e auditado.

**Situação:** Não implementado

---

## UC-051-03 — Ler a síntese automática de uma turma ou avaliação

**Ator:** a definir
**Permissão:** a definir
**Escopo:** turma ou prova no escopo
**Pré-condições:** dados suficientes.
**Pós-condições:** nenhuma.

**Fluxo principal:**
1. O sistema gera uma síntese textual a partir dos dados autorizados.
2. A síntese indica as bases usadas e os limites.

**Critérios de aceite:**
- [ ] A síntese não usa dados fora do escopo do usuário.

**Situação:** Não implementado
