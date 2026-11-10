# 062 — Inventário de dados e retenção — Casos de uso

**Natureza:** existente documentada a partir dos registros; rotinas de retenção propostas
**Perfis envolvidos:** responsável pela plataforma (controlador/operador); operação (rotinas agendadas); visitante (lista pública). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-062-01 — Manter o registro das operações com dados pessoais

**Ator:** responsável pela plataforma, apoiado por lucasgenial
**Permissão:** não se aplica (documento versionado)
**Escopo:** plataforma
**Pré-condições:** nova operação, campo ou terceiro introduzido por uma funcionalidade.
**Pós-condições:** docs/34 atualizado com finalidade, base, local, prazo e terceiros.
**Tela/rota:** docs/34 (`docs/34-inventario-de-dados.md`)

**Fluxo principal:**
1. Uma entrega cria ou altera um tratamento de dados pessoais.
2. O registro da operação é atualizado no inventário, com justificativa de necessidade (RL1).
3. O responsável confirma base e prazo.

**Fluxos alternativos e de erro:**
- 3a. Prazo não decidido: a operação fica marcada como pendente, sem prazo presumido.

**Regras de negócio:** RL1, RL2.

**Critérios de aceite:**
- [x] Inventário O01–O18 publicado (PR #68).
- [ ] Inventário reflete o estado posterior a L3 e L6 (⚠ docs/34 §4 está desatualizado; ver §6 do plano mestre).

**Situação:** Parcial — evidência: docs/34, docs/39. ⚠ Atualização pós-L3/L6 pendente; resolve: subfase 2.5 (revisão junto com os prazos abertos).

---

## UC-062-02 — Aplicar a retenção aprovada por rotina automática

**Ator:** agendador da API (operação)
**Permissão:** não se aplica (processo do sistema)
**Escopo:** plataforma
**Pré-condições:** decisão §4 item 11 aprovada; prazos do docs/39 §2 vigentes.
**Pós-condições:** registros vencidos removidos ou reduzidos; contagens registradas sem dados pessoais.
**Tela/rota:** comandos agendados em `apps/api/routes/console.php` (a criar).

**Fluxo principal:**
1. A rotina diária seleciona registros vencidos de cada grupo (logs, jobs falhos, resultado e recibo B6, tokens vencidos, contatos sem contrato).
2. Exclui em lotes, respeitando exceções registradas.
3. Registra o total por grupo.

**Fluxos alternativos e de erro:**
- 2a. Registro sob exceção (incidente, atendimento, preservação): mantido até a data de revisão.
- 2b. Recibo B6 ainda necessário a retentativa: mantido.

**Regras de negócio:** RL6; docs/39 §2.

**Critérios de aceite:**
- [ ] Cada grupo tem teste com registros antes e depois do prazo.
- [ ] Execução repetida não altera o resultado.
- [ ] Nenhum dado pessoal aparece no registro da rotina.

**Situação:** Não implementado — o agendador só tem inadimplência e cópias T5; não há comando de retenção.

---

## UC-062-03 — Consultar a lista pública de suboperadores

**Ator:** visitante anônimo
**Permissão:** nenhuma
**Escopo:** público
**Pré-condições:** contratos de terceiros conferidos (subfase 2.6).
**Pós-condições:** nenhuma.
**Tela/rota:** a definir; referenciada pelo aviso de privacidade (FN-061, campo `[LINK DOS SUBOPERADORES]`).

**Fluxo principal:**
1. O visitante segue o link do aviso de privacidade.
2. O sistema mostra fornecedor, finalidade, país e mecanismo de transferência.

**Fluxos alternativos e de erro:**
- Nenhum.

**Regras de negócio:** RL12.

**Critérios de aceite:**
- [ ] Lista cobre Cloudflare (incluindo Web Analytics), Resend e os demais terceiros do docs/34 §6 e docs/39 §4.

**Situação:** Não implementado.

---

## UC-062-04 — Validar o relatório de impacto do portal

**Ator:** responsável pela plataforma e instituição controladora
**Permissão:** não se aplica
**Escopo:** plataforma e instituição
**Pré-condições:** controles do portal implementados (FN-067) e evidências reunidas.
**Pós-condições:** risco residual de R01–R11 registrado e decisão do controlador assinada.
**Tela/rota:** docs/38 §8 (`docs/38-ripd-preliminar-portal.md`)

**Fluxo principal:**
1. Para cada risco, registrar controle, versão, evidência de teste e revisor.
2. Reavaliar probabilidade e impacto.
3. O controlador decide aceitar, reduzir ou evitar.

**Fluxos alternativos e de erro:**
- 2a. Controle só planejado: não reduz o risco residual (regra do docs/38 §8).

**Regras de negócio:** RL13.

**Critérios de aceite:**
- [ ] Todos os campos do quadro do docs/38 §8 preenchidos.

**Situação:** Parcial — avaliação técnica preliminar entregue (PR #71); validação pendente.
