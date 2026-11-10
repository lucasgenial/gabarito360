# 030 — Autocadastro, planos e assinatura — Casos de uso

**Natureza:** existente documentada a partir do código (funcionalidade substituída em parte)
**Perfis envolvidos:** visitante (público); titular de assinatura. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Registro resumido do que o código faz hoje. ⚠ marca a diferença em relação ao planejado no docs/13 (MP-027..029) e no docs/06 (RN-015/016); o destino depende do §4 item 5 do [plano mestre](../../planejamento/00-plano-mestre.md).

---

## UC-030-01 — Consultar planos

**Ator:** visitante
**Permissão:** nenhuma (público)
**Escopo:** público
**Tela/rota:** `/planos`; `GET /api/v1/planos`.

**Fluxo principal:** o visitante abre a página de planos e vê as opções; a ação de contratar leva ao contato (UC-030-02).

**Situação:** Implementado — evidência: `PlanoController` (web/API). ⚠ Preços dos planos comerciais "a definir" (RN-015.1) → FN-068.

---

## UC-030-02 — Pedir contratação pelo Fale Conosco

**Ator:** visitante
**Permissão:** nenhuma (público)
**Tela/rota:** `/fale-conosco` → `POST /fale-conosco` → `/fale-conosco/obrigado`; `POST /api/v1/leads` (5/min).

**Fluxo principal:**
1. O visitante preenche o formulário de contato.
2. O sistema registra o pedido e mostra a confirmação.
3. A equipe faz a contratação assistida e cadastra o cliente (UC-023-01).

**Fluxos alternativos e de erro:** excesso de envios → `429`.

**Situação:** Implementado — evidência: `LeadController`; PR #59. O formulário e o e-mail são detalhados em FN-060.

---

## UC-030-03 — Tentar o checkout antigo

**Ator:** visitante ou usuário
**Tela/rota:** `/checkout` (GET/POST); `POST /api/v1/assinaturas/checkout`.

**Fluxo principal:** a web redireciona para o Fale Conosco, preservando `plano_id`; a API responde `409 'Para contratar, fale com nossa equipe pelo Fale Conosco.'` com a URL de contato.

**Situação:** Implementado (como redirecionamento) — ⚠ o checkout com Mercado Pago do MP-027 foi **substituído**; páginas `/checkout/sucesso|falha|pendente` e o webhook seguem registrados sem uso. Destino: §4 item 5.

---

## UC-030-04 — Consultar e cancelar a própria assinatura

**Ator:** titular da assinatura
**Permissão:** autenticado (sem chave específica)
**Tela/rota:** `/app/assinatura`, `POST /app/assinatura/cancelar`; `GET /api/v1/assinaturas/minha`, `POST /api/v1/assinaturas/cancelar`.

**Fluxo principal:** o titular vê plano e pagamentos da última assinatura; pode cancelar uma assinatura em `trial` ou `ativa`.

**Fluxos alternativos e de erro:** sem assinatura → `404 'Nenhuma assinatura encontrada.'`.

**Situação:** Implementado — ⚠ sem novas assinaturas desde a troca do checkout; a relação com cliente/contrato (FN-023, FN-068) não existe. Destino: §4 item 5.

---

## UC-030-05 — Cadastrar-se sozinho (individual ou institucional)

**Ator:** visitante
**Fluxo planejado (MP-028, RN-016.1):** escolher o tipo de conta, criar a conta e a estrutura inicial.

**Situação:** Não implementado — **substituído** por convites (FN-015), cadastro interno de clientes (FN-023) e espaço pessoal (FN-022). Não há rota de autocadastro.

---

## UC-030-06 — Absorver rede individual numa rede institucional

**Ator:** gestor institucional (gera convite); professor titular (confirma)
**Fluxo planejado (MP-029, RN-016.4):** migrar os dados da rede individual para a institucional.

**Situação:** Não implementado — provavelmente obsoleto; a necessidade é coberta por FN-025 e FN-027 (PA1-C). Decisão: §4 item 5.
