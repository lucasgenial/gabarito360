# 068 — Financeiro (contratos, faturas, pagamentos) — Casos de uso

**Natureza:** evolução de existente (o legado de assinatura do MP-027 é documentado a partir do código)
**Perfis envolvidos:** Financeiro, Desenvolvedor e Suporte (contexto interno); titular de assinatura legada; provedor de pagamento (sistema externo). Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1.
**Atualizado em:** 2026-11-11

Os casos 01 a 04 descrevem o legado do MP-027 como está no código. Os demais são propostos para as fases F1 a F3 do [planejamento](planejamento.md).

O consumo de créditos tem casos de uso próprios na [FN-081](../081-creditos-correcao/casos-de-uso.md). Esta funcionalidade cobra; a FN-081 credita e consome.

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto.

---

## UC-068-01 — Tentar contratar pelo checkout (contratação assistida)

**Ator:** visitante ou usuário autenticado
**Permissão:** web pública; API autenticada
**Escopo:** não se aplica
**Pré-condições:** nenhuma
**Pós-condições:** nenhuma assinatura criada; o visitante é levado ao Fale Conosco
**Tela/rota:** web `GET/POST /checkout` (`CheckoutController`, redireciona para `/fale-conosco`); API `POST /assinaturas/checkout`

**Fluxo principal:**
1. O usuário escolhe um plano em `/planos` e segue para o checkout.
2. A web redireciona ao Fale Conosco com os dados do plano.
3. A API, se chamada, responde 409 "Para contratar, fale com nossa equipe pelo Fale Conosco." com `contato_url`.

**Fluxos alternativos e de erro:**
- 2a. `/checkout/sucesso`, `/falha` e `/pendente` continuam acessíveis como páginas de retorno do Mercado Pago, sem fluxo que leve a elas.

**Regras de negócio:** contratação por contato (PR #59); [RN-GER-09](../../arquitetura/08-regras-de-negocio.md) (Substituída) registra a troca do checkout pela contratação assistida.

**Critérios de aceite:**
- [x] Nenhuma cobrança é criada (`ContratacaoContatoTest`).
- [ ] ⚠ As páginas de retorno e os textos de checkout permanecem. Resolve: decisão do §8 do planejamento e subfase 3.1.

**Situação:** Implementado (legado, checkout desativado) — evidência: `AssinaturaController::checkout`, `CheckoutController`

---

## UC-068-02 — Consultar a própria assinatura (legado)

**Ator:** titular da assinatura
**Permissão:** autenticado; sem chave de permissão
**Escopo:** assinatura em que `titular_id` é o usuário
**Pré-condições:** assinatura existente
**Pós-condições:** nenhuma
**Tela/rota:** web `GET /assinatura`; API `GET /assinaturas/minha`

**Fluxo principal:**
1. A API devolve a assinatura mais recente com plano, licenças, situação, datas de teste e de próxima cobrança, e o histórico de pagamentos.

**Fluxos alternativos e de erro:**
- 1a. Sem assinatura: 404 "Nenhuma assinatura encontrada."

**Regras de negócio:** [RN-GER-09](../../arquitetura/08-regras-de-negocio.md) (só o titular vê a assinatura).

**Critérios de aceite:**
- [x] Só a assinatura do próprio titular.
- [ ] ⚠ A assinatura está ligada a um usuário titular, não a um cliente. Os valores são `decimal`. Os estados são `trial`, `ativa`, `atrasada` e `cancelada`. Resolve: subfase 1.1 (F1), com os estados do planejamento §6.3.

**Situação:** Implementado (legado) — evidência: `AssinaturaController::minha`

---

## UC-068-03 — Cancelar a própria assinatura (legado)

**Ator:** titular da assinatura
**Permissão:** autenticado; sem chave de permissão
**Escopo:** assinatura própria em `trial` ou `ativa`
**Pré-condições:** assinatura ativa
**Pós-condições:** situação `cancelada`; a assinatura recorrente é cancelada no Mercado Pago quando é mensal e tem identificador do gateway
**Tela/rota:** web `POST /assinatura/cancelar`; API `POST /assinaturas/cancelar`

**Fluxo principal:**
1. O titular pede o cancelamento.
2. A API cancela no gateway, se aplicável, e marca `cancelada`: "Assinatura cancelada com sucesso."

**Fluxos alternativos e de erro:**
- 1a. Sem assinatura ativa: 404.

**Regras de negócio:** [RN-GER-09](../../arquitetura/08-regras-de-negocio.md).

**Critérios de aceite:**
- [ ] ⚠ A chamada ao `MercadoPagoService` é direta, sem interface de gateway, fila ou auditoria. Resolve: subfase 3.1 (F3).

**Situação:** Implementado (legado) — evidência: `AssinaturaController::cancelar`

---

## UC-068-04 — Receber webhook do Mercado Pago (legado)

**Ator:** Mercado Pago (sistema externo)
**Permissão:** rota pública, validada por assinatura HMAC (`x-signature`, `x-request-id`)
**Escopo:** assinatura do titular indicado no pagamento
**Pré-condições:** segredo do webhook configurado
**Pós-condições:** pagamento registrado e situação da assinatura atualizada
**Tela/rota:** API `POST /webhooks/mercadopago` (`WebhookController::mercadoPago`)

**Fluxo principal:**
1. A API valida a assinatura do webhook.
2. Para pagamento, ela consulta o provedor e atualiza pagamento e assinatura. Para pré-aprovação, ela atualiza a assinatura recorrente.

**Fluxos alternativos e de erro:**
- 1a. Assinatura inválida: recusado.

**Regras de negócio:** [RN-GER-09](../../arquitetura/08-regras-de-negocio.md).

**Critérios de aceite:**
- [ ] ⚠ O processamento ocorre dentro da requisição, sem fila. A rota está ativa, embora não haja checkout. Resolve: subfase 3.1 (F3) e a decisão do §8 do planejamento.

**Situação:** Implementado (legado) — evidência: `WebhookController`, `MercadoPagoService::validarWebhook`

---

## UC-068-05 — Registrar contrato de um cliente

**Ator:** Financeiro (ou Desenvolvedor)
**Permissão:** chave financeira a aprovar, no contexto interno
**Escopo:** cliente
**Pré-condições:** cliente cadastrado (FN-023); F1 entregue
**Pós-condições:** contrato com itens, vigência e modalidade gravado e auditado
**Tela/rota:** cadastro do cliente, contexto interno (mockup na subfase 2.0)

**Fluxo principal:**
1. O Financeiro informa itens, valores em centavos, vigência, desconto ou cortesia.
2. Para cliente público, ele informa processo, empenho e vigência.
3. Ele salva. A operação é auditada.

**Fluxos alternativos e de erro:**
- 1a. Modalidade `presenteado`: contrato sem cobrança, com registro da cortesia.

**Regras de negócio:** planejamento §2 (F1) e §6.

**Critérios de aceite:**
- [ ] Testes de contrato com itens, desconto e cortesia.
- [ ] Nenhum dado de aluno acessível nas telas.
- [ ] Nenhum preço fica fixo no código ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)).

**Situação:** Não implementado — subfases 1.1 e 2.1.

---

## UC-068-06 — Emitir fatura e registrar pagamento manual

**Ator:** Financeiro
**Permissão:** chave financeira a aprovar
**Escopo:** cliente
**Pré-condições:** contrato vigente
**Pós-condições:** fatura e cobrança criadas; pagamento baixado manualmente; nota fiscal referenciada
**Tela/rota:** cadastro do cliente (mockup na subfase 2.0)

**Fluxo principal:**
1. O Financeiro gera a fatura do período.
2. Ao receber, ele registra a baixa manual com data, valor e meio.
3. Ele registra a nota fiscal.

**Fluxos alternativos e de erro:**
- 2a. Valor diferente da fatura: o sistema exige justificativa.
- 2b. Estorno: lançamento novo, sem apagar o anterior.

**Regras de negócio:** planejamento §6.1 (append-only).

**Critérios de aceite:**
- [ ] Testes de fatura e pagamento manual.
- [ ] Toda baixa e todo estorno são auditados.
- [ ] Nenhum registro financeiro é alterado ou apagado.

**Situação:** Não implementado — subfases 1.1 e 2.1.

---

## UC-068-07 — Acompanhar recebimentos e inadimplência

**Ator:** Financeiro
**Permissão:** chave financeira a aprovar
**Escopo:** todos os clientes
**Pré-condições:** faturas existentes
**Pós-condições:** nenhuma
**Tela/rota:** painel de recebimentos (mockup na subfase 2.0)

**Fluxo principal:**
1. O Financeiro vê recebido, a receber e vencido, com filtro por período e cliente.
2. Ele abre o cliente inadimplente para agir.

**Fluxos alternativos e de erro:**
- 2a. Bloqueio de acesso por inadimplência: indisponível até o responsável aprovar a regra.

**Regras de negócio:** planejamento §8 (bloqueio por inadimplência). O estado `past_due` não implica bloqueio total imediato.

**Critérios de aceite:**
- [ ] Nenhum bloqueio automático sem regra aprovada.
- [ ] A falta de saldo de créditos bloqueia só o recurso tarifado, nunca o resto do sistema (FN-081).

**Situação:** Não implementado — subfase 2.1.

---

## UC-068-08 — Cobrar por um provedor de pagamento

**Ator:** Financeiro; provedor (webhook)
**Permissão:** chave financeira a aprovar; webhook com assinatura verificada
**Escopo:** cliente
**Pré-condições:** F3 entregue
**Pós-condições:** cobrança criada no provedor; eventos processados pela fila sem repetir efeito
**Tela/rota:** serviço `GatewayPagamento`; webhook único por provedor

**Fluxo principal:**
1. O Financeiro cria a cobrança pela interface do gateway (manual ou provedor).
2. O provedor notifica por webhook. A API valida a assinatura e enfileira o evento.
3. O evento atualiza cobrança e pagamento uma única vez.

**Fluxos alternativos e de erro:**
- 1a. Cancelar e estornar seguem a mesma interface.
- 2a. Evento repetido: ignorado pela chave de idempotência.

**Regras de negócio:** planejamento §2 (F3). Billing não é Payments: o gateway só emite eventos ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §2). Nunca guardar número de cartão ou CVV.

**Critérios de aceite:**
- [ ] Um provedor falso de teste passa por todo o fluxo.
- [ ] Teste de evento repetido.
- [ ] Os eventos do gateway são `payment_confirmed`, `payment_failed`, `refund_confirmed` e `chargeback`.

**Situação:** Não implementado — subfase 3.1.

---

## UC-068-09 — Cobrar a compra avulsa de créditos

**Ator:** Financeiro ou sistema; responsável pelo contexto de cobrança (comprador)
**Permissão:** chave financeira a aprovar
**Escopo:** cliente e contexto de cobrança
**Pré-condições:** D-4 decidida (formato do pacote, [FN-081 §8](../081-creditos-correcao/planejamento.md)); F3 entregue
**Pós-condições:** cobrança gerada e paga; a FN-081 credita o lote `PURCHASED`
**Tela/rota:** a desenhar na subfase 2.0

**Fluxo principal:**
1. A FN-081 pede a cobrança de um pacote de créditos (UC-081-05).
2. A FN-068 gera a fatura e a cobrança.
3. Com o pagamento confirmado, a FN-068 emite o evento `payment_confirmed`.
4. A FN-081 concede o lote na carteira do contexto.

**Fluxos alternativos e de erro:**
- 3a. Evento repetido: a FN-068 não gera cobrança nova e o crédito não entra duas vezes.
- 3b. Pagamento recusado: nenhum crédito é concedido.

**Regras de negócio:** planejamento §6.3, regra 3. A FN-068 nunca escreve em tabela `billing_`.

**Critérios de aceite:**
- [ ] Nenhum crédito entra antes da confirmação do pagamento.
- [ ] A FN-068 não grava saldo nem lote de carteira.
- [ ] ⚠ O direito de arrependimento, o reembolso e o pacote parcialmente consumido dependem da pendência jurídica 1 (planejamento §8).

**Situação:** Não implementado — subfases 1.1 e 3.1.

---

## UC-068-10 — Publicar plano, preço e franquia no catálogo

**Ator:** Financeiro (ou Desenvolvedor)
**Permissão:** chave financeira a aprovar
**Escopo:** catálogo comercial
**Pré-condições:** D-3 decidida (quantidade de franquia por plano, [FN-081 §8](../081-creditos-correcao/planejamento.md)); F1 entregue
**Pós-condições:** produto, recurso, plano, preço e entitlement disponíveis para assinatura
**Tela/rota:** a desenhar na subfase 2.0

**Fluxo principal:**
1. O Financeiro cadastra o produto e o recurso tarifado.
2. Ele cadastra o plano, o preço em centavos e a franquia do recurso por ciclo.
3. O plano fica disponível para assinatura.

**Fluxos alternativos e de erro:**
- 2a. Preço ou franquia ausentes: o plano não é publicado.

**Regras de negócio:** [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §1 e §5. O catálogo é orientado a produto: um produto novo entra como registro, não como código.

**Critérios de aceite:**
- [ ] Nenhum preço, franquia ou regra comercial crítica fica fixo no código.
- [ ] Um produto novo não exige código novo.
- [ ] A franquia publicada aqui é a que a FN-081 consome no ciclo.

**Situação:** Não implementado — subfase 1.1.

---

## UC-068-11 — Efetivar a assunção financeira de uma escola autônoma

**Ator:** Financeiro, com Suporte ou Desenvolvedor; instância superior (rede) como parte
**Permissão:** a definir
**Escopo:** clientes e espaços envolvidos
**Pré-condições:** o responsável definiu as regras de alcance, vigência e período já pago; FN-029
**Pós-condições:** responsabilidade financeira transferida por fluxo auditável, com escolas e espaços delimitados
**Tela/rota:** a desenhar na subfase 2.0

**Fluxo principal:**
1. A instância superior formaliza a assunção.
2. O Financeiro registra alcance e vigência. O sistema audita.
3. Só então a autorização de acesso prevista na [RN-EIN-07](../../arquitetura/08-regras-de-negocio.md) pode ser concedida (FN-029).

**Fluxos alternativos e de erro:**
- 1a. Associação ao catálogo, contrato da rede com outras escolas ou pagamento avulso não efetivam a assunção.

**Regras de negócio:** [RN-EIN-07 e RN-EIN-08](../../arquitetura/08-regras-de-negocio.md).

**Critérios de aceite:**
- [ ] Nenhum acesso é ampliado sem assunção efetivada e auditada.
- [ ] Dados, autoria e documentos emitidos são preservados.
- [ ] ⚠ A RN-EIN-08 ("o sistema não devolve valores já pagos") vale neste fluxo. Ela não vale como regra do novo billing. O conflito com a pendência jurídica 1 está registrado no planejamento §8.

**Situação:** Não implementado — depende das decisões do §8.

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-10 | Casos de uso criados a partir do código e do tronco original | lucasgenial |
| 2 | 2026-11-11 | Subfase 0.2 de adequação: citações aos documentos antigos trocadas pelos destinos do mapa; RN-015 passou a RN-GER-09 e as regras da escola autônoma a RN-EIN; UC-068-09 (compra avulsa de créditos) e UC-068-10 (catálogo) acrescentados; fronteira com a FN-081 declarada | lucasgenial |
