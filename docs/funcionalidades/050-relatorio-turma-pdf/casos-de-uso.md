# 050 — Relatório PDF da turma — Casos de uso

**Natureza:** evolução de existente
**Perfis envolvidos:** perfis pedagógicos com `relatorio.turma` (Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor, Coordenador, Professor, Professor autônomo). Ver docs/03 (`docs/03-perfis-e-permissoes.md`) §4.6.
**Atualizado em:** 2026-11-09

Relaciona-se ao UC-010 do docs/07 (`docs/07-casos-de-uso.md`) (exportar relatório em PDF).

---

## UC-050-01 — Emitir o PDF do recorte do painel da turma (versão atual)

**Ator:** perfis pedagógicos com `relatorio.turma`
**Permissão:** `relatorio.turma`
**Escopo:** turma no escopo; perfis administrativos não têm acesso
**Pré-condições:** turma com avaliações no recorte (ou recorte vazio).
**Pós-condições:** PDF entregue e emissão registrada.
**Tela/rota:** painel da turma (`turmas.show`), web `turmas.painelPdf`; API `GET v1/turmas/{id}/painel/pdf`

**Fluxo principal:**
1. No painel da turma, o usuário aplica (ou não) o recorte e escolhe retrato ou paisagem.
2. A API monta o mesmo DTO do painel, gera o PDF e revalida acesso e dados.
3. O PDF abre em linha ou é baixado.

**Fluxos alternativos e de erro:**
- 1a. Filtro fora do recorte autorizado: "Disciplina fora do recorte autorizado." ou "Professor autor fora do recorte autorizado." (422).
- 2a. Falha do gerador: "Não foi possível gerar o documento." (503).
- 2b. Dados mudaram durante a geração: "Os dados mudaram. Gere novamente o relatório." (409).

**Critérios de aceite:**
- [x] O PDF usa o mesmo recorte do painel.
- [ ] ⚠ Conteúdo atual: ainda por questões, sem listas nominais (docs/176). A paridade é o UC-050-02.

**Situação:** Parcial — evidência: `PainelTurmaController::pdf`, `omr/gerar_painel_turma.py`; testes `PainelTurmaLimitePdfTest`

---

## UC-050-02 — Emitir o relatório da turma com paridade ao painel

**Ator:** perfis pedagógicos com `relatorio.turma`
**Permissão:** `relatorio.turma`
**Escopo:** turma no escopo
**Pré-condições:** P3.2 vigente (bases e exclusões).
**Pós-condições:** PDF com os mesmos números do painel no mesmo recorte.
**Tela/rota:** a mesma do UC-050-01

**Fluxo principal:**
1. O usuário emite o PDF a partir do painel.
2. O documento traz seis KPIs, três análises, grupo Atenção com critério e Top 5, todas as linhas autorizadas e a indicação de empates.

**Fluxos alternativos e de erro:**
- 2a. Sem dados no recorte: o PDF informa a ausência sem usar dados demonstrativos.

**Regras de negócio:** docs/177 P8.1.2–P8.1.5; nenhuma ilustração ou modelo em relatório real.

**Critérios de aceite:**
- [ ] Números iguais aos do painel em ao menos três recortes conferidos.
- [ ] Listas longas e nomes extensos não quebram a paginação.
- [ ] Empates aparecem de forma explícita.

**Situação:** Não implementado — WIP na branch `feat/tu1-pdf-relatorio`; subfases 1.1–1.4
