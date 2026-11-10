# 039 — Períodos letivos — Casos de uso

**Natureza:** nova (candidata; casos de uso propostos de alto nível)
**Perfis envolvidos:** a definir em P4.2.1 (provavelmente Diretor Escolar, Coordenador, Secretário Escolar; Professor autônomo no espaço pessoal); consulta por quem vê painéis — ver [docs/03](../../03-perfis-e-permissoes.md)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto.

---

## UC-039-01 — Configurar o calendário acadêmico

**Ator:** gestor da escola (a definir); Professor autônomo no espaço pessoal
**Permissão:** a definir
**Escopo:** escola | turma | espaço pessoal
**Pré-condições:** FN-056 C2 disponível.
**Pós-condições:** ciclo e períodos registrados com versão.
**Tela/rota:** a desenhar

**Fluxo principal:**
1. O ator cria o ano/ciclo e suas subdivisões (bimestres, trimestres, semestres, unidades, organização da EJA).
2. Associa o calendário às turmas; turmas da mesma escola podem ter calendários diferentes.

**Fluxos alternativos e de erro:**
- 1a. Sobreposição indevida de períodos → recusa, sem impedir subdivisões legítimas.

**Critérios de aceite:**
- [ ] Nomes iguais em calendários diferentes não são equiparados.

**Situação:** Não implementado — origem: docs/177 P4.1, P4.2.

---

## UC-039-02 — Encerrar, reabrir e corrigir períodos com histórico

**Ator:** gestor da escola (a definir)
**Permissão:** a definir
**Escopo:** escola
**Pré-condições:** período em uso.
**Pós-condições:** nova versão; histórico preservado.
**Tela/rota:** a desenhar

**Fluxo principal:**
1. O ator altera ou encerra o período; o sistema mostra o impacto nos registros associados.
2. Confirma; a versão anterior fica no histórico.

**Critérios de aceite:**
- [ ] Nenhuma conversão silenciosa de datas em períodos.

**Situação:** Não implementado — origem: docs/177 P4.2.3–P4.2.5.

---

## UC-039-03 — Filtrar resultados por período nos painéis e avaliações

**Ator:** perfis com acesso a painéis e relatórios
**Permissão:** `relatorio.turma` e afins
**Escopo:** turma | escola
**Pré-condições:** calendário configurado.
**Pós-condições:** nenhuma.
**Tela/rota:** seletor de período dos painéis (já desenhado)

**Fluxo principal:**
1. O ator escolhe período, intervalo livre ou acumulado do ciclo.
2. Os painéis recalculam sem contar duas vezes semestre e subdivisões.

**Critérios de aceite:**
- [ ] Recuperação conta no período recuperado.
- [ ] Registros sem classificação aparecem como "Sem período definido".

**Situação:** Não implementado — origem: docs/177 P4.3. ⚠ Hoje há só o widget demonstrativo (subfase 0.1); o recorte funcional é por intervalo de datas ([UC-036-05](../036-organizacao-turma/casos-de-uso.md)).

---

## UC-039-04 — Registrar elegibilidade e participação por aplicação

**Ator:** Coordenador, Professor (a definir)
**Permissão:** a definir
**Escopo:** turma
**Pré-condições:** FN-026 (MT1) concluída.
**Pós-condições:** conjunto esperado de estudantes e participação (presente, dispensa, ausência confirmada, sem informação) registrados.
**Tela/rota:** a desenhar

**Critérios de aceite:**
- [ ] Períodos antigos sem evidência mostram indisponibilidade explicada, sem reconstrução presumida.

**Situação:** Não implementado — origem: docs/177 P4.4.
