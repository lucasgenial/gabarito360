# 068 — Financeiro (contratos, faturas, pagamentos) — Casos de uso

**Natureza:** nova, com legado de assinatura existente documentado a partir do código
**Perfis envolvidos:** Financeiro, Desenvolvedor e Suporte (contexto interno); titular de assinatura legada; provedor de pagamento (sistema externo). Ver [docs/03](../../03-perfis-e-permissoes.md), extensão A1.
**Atualizado em:** 2026-11-10

Os casos 01 a 04 descrevem o legado do MP-027 como está no código. Os demais são propostos a partir do docs/28 (F1–F3) e do docs/163.

---

## UC-068-01 — Tentar contratar pelo checkout (contratação assistida)

**Ator:** visitante ou usuário autenticado
**Permissão:** web pública; API autenticada
**Escopo:** não se aplica
**Pré-condições:** nenhuma
**Pós-condições:** nenhuma assinatura criada; visitante levado ao Fale Conosco
**Tela/rota:** web `GET/POST /checkout` (`CheckoutController`, redireciona para `/fale-conosco`); API `POST /assinaturas/checkout`

**Fluxo principal:**
1. O usuário escolhe um plano em `/planos` e segue para o checkout.
2. A web redireciona ao Fale Conosco com os dados do plano.
3. A API, se chamada, responde 409 "Para contratar, fale com nossa equipe pelo Fale Conosco." com `contato_url`.

**Fluxos alternativos e de erro:**
- 2a. `/checkout/sucesso`, `/falha` e `/pendente` continuam acessíveis como páginas de retorno do Mercado Pago, sem fluxo que leve a elas.

**Regras de negócio:** contratação por contato (PR #59); RN-015.1 e RN-016 do docs/06 ainda descrevem checkout — ver decisão §4 item 5.

**Critérios de aceite:**
- [x] Nenhuma cobrança é criada (`ContratacaoContatoTest`).
- [ ] ⚠ Páginas de retorno e textos de checkout permanecem. Resolve: decisão §4 item 5 e subfase 3.1.

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
1. A API devolve a assinatura mais recente com plano, licenças, situação, datas de teste e próxima cobrança e o histórico de pagamentos.

**Fluxos alternativos e de erro:**
- 1a. Sem assinatura: 404 "Nenhuma assinatura encontrada."

**Regras de negócio:** RN-015.2 (só o titular vê a assinatura).

**Critérios de aceite:**
- [x] Só a assinatura do próprio titular.
- [ ] ⚠ Ligada a usuário titular, não a cliente; valores em decimal. Resolve: subfase 1.1 (F1).

**Situação:** Implementado (legado) — evidência: `AssinaturaController::minha`

---

## UC-068-03 — Cancelar a própria assinatura (legado)

**Ator:** titular da assinatura
**Permissão:** autenticado; sem chave de permissão
**Escopo:** assinatura própria em `trial` ou `ativa`
**Pré-condições:** assinatura ativa
**Pós-condições:** situação `cancelada`; assinatura recorrente cancelada no Mercado Pago quando mensal com identificador do gateway
**Tela/rota:** web `POST /assinatura/cancelar`; API `POST /assinaturas/cancelar`

**Fluxo principal:**
1. Titular pede o cancelamento.
2. A API cancela no gateway, se aplicável, e marca `cancelada`: "Assinatura cancelada com sucesso."

**Fluxos alternativos e de erro:**
- 1a. Sem assinatura ativa: 404.

**Regras de negócio:** RN-015.3.

**Critérios de aceite:**
- [ ] ⚠ Chamada direta ao `MercadoPagoService`, sem interface de gateway, fila ou auditoria. Resolve: subfase 3.1 (F3).

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
2. Para pagamento, consulta o provedor e atualiza pagamento e assinatura; para pré-aprovação, atualiza a assinatura recorrente.

**Fluxos alternativos e de erro:**
- 1a. Assinatura inválida: recusado.

**Regras de negócio:** RN-015.3.

**Critérios de aceite:**
- [ ] ⚠ Processamento dentro da requisição, sem fila; rota ativa embora não haja checkout. Resolve: subfase 3.1 (F3) e decisão do §8 do planejamento.

**Situação:** Implementado (legado) — evidência: `WebhookController`, `MercadoPagoService::validarWebhook`

---

## UC-068-05 — Registrar contrato de um cliente

**Ator:** Financeiro (ou Desenvolvedor)
**Permissão:** chave financeira a aprovar, no contexto interno
**Escopo:** cliente
**Pré-condições:** cliente cadastrado (FN-023); F1 entregue
**Pós-condições:** contrato com itens, vigência e modalidade gravado e auditado
**Tela/rota:** cadastro do cliente, contexto interno (a desenhar)

**Fluxo principal:**
1. Financeiro informa itens, valores em centavos, vigência, desconto ou cortesia.
2. Para cliente público, informa processo, empenho e vigência.
3. Salva; a operação é auditada.

**Fluxos alternativos e de erro:**
- 1a. Modalidade `presenteado`: contrato sem cobrança, com registro da cortesia.

**Regras de negócio:** docs/28 F1.

**Critérios de aceite:**
- [ ] Testes de contrato com itens, desconto e cortesia.
- [ ] Nenhum dado de aluno acessível nas telas.

**Situação:** Não implementado — subfases 1.1 e 2.1.

---

## UC-068-06 — Emitir fatura e registrar pagamento manual

**Ator:** Financeiro
**Permissão:** chave financeira a aprovar
**Escopo:** cliente
**Pré-condições:** contrato vigente
**Pós-condições:** fatura e cobrança criadas; pagamento baixado manualmente; nota fiscal referenciada
**Tela/rota:** cadastro do cliente (a desenhar)

**Fluxo principal:**
1. Financeiro gera a fatura do período.
2. Ao receber, registra a baixa manual com data, valor e meio.
3. Registra a nota fiscal.

**Fluxos alternativos e de erro:**
- 2a. Valor diferente da fatura: exige justificativa.
- 2b. Estorno: novo lançamento, sem apagar o anterior.

**Regras de negócio:** docs/28 F1 e F2 ("tudo funciona com lançamento manual").

**Critérios de aceite:**
- [ ] Testes de fatura e pagamento manual.
- [ ] Toda baixa e estorno auditados.

**Situação:** Não implementado — subfases 1.1 e 2.1.

---

## UC-068-07 — Acompanhar recebimentos e inadimplência

**Ator:** Financeiro
**Permissão:** chave financeira a aprovar
**Escopo:** todos os clientes
**Pré-condições:** faturas existentes
**Pós-condições:** nenhuma
**Tela/rota:** painel de recebimentos (a desenhar)

**Fluxo principal:**
1. Financeiro vê recebido, a receber e vencido, com filtro por período e cliente.
2. Abre o cliente inadimplente para agir.

**Fluxos alternativos e de erro:**
- 2a. Bloqueio de acesso por inadimplência: indisponível até a regra ser definida pelo responsável.

**Regras de negócio:** docs/28 F2; RN-015.4 (em conflito, ver §8 do planejamento).

**Critérios de aceite:**
- [ ] Nenhum bloqueio automático sem regra aprovada.

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
1. Financeiro cria a cobrança pela interface do gateway (manual ou provedor).
2. O provedor notifica por webhook; a API valida a assinatura e enfileira o evento.
3. O evento atualiza cobrança e pagamento uma única vez.

**Fluxos alternativos e de erro:**
- 2a. Evento repetido: ignorado pela chave de idempotência.
- 1a. Cancelar e estornar seguem a mesma interface.

**Regras de negócio:** docs/28 F3; nunca guardar número de cartão ou CVV.

**Critérios de aceite:**
- [ ] Um provedor falso de teste passa por todo o fluxo.
- [ ] Teste de evento repetido.

**Situação:** Não implementado — subfase 3.1.

---

## UC-068-09 — Efetivar a assunção financeira de uma escola autônoma

**Ator:** Financeiro, com Suporte/Desenvolvedor; instância superior (rede) como parte
**Permissão:** a definir
**Escopo:** clientes e espaços envolvidos
**Pré-condições:** regras de alcance, vigência e período já pago definidas pelo responsável; FN-029
**Pós-condições:** responsabilidade financeira transferida por fluxo auditável, delimitando escolas e espaços; nenhum valor devolvido
**Tela/rota:** a desenhar

**Fluxo principal:**
1. A instância superior formaliza a assunção.
2. Financeiro registra alcance e vigência; o sistema audita.
3. Só então a autorização de acesso prevista no docs/163 pode ser concedida (FN-029).

**Fluxos alternativos e de erro:**
- 1a. Associação ao catálogo, contrato da rede com outras escolas ou pagamento avulso não efetivam a assunção.

**Regras de negócio:** docs/163 (regra comercial adicional aprovada; sem devolução de valores pagos).

**Critérios de aceite:**
- [ ] Nenhum acesso é ampliado sem assunção efetivada e auditada.
- [ ] Dados, autoria e documentos emitidos preservados.

**Situação:** Não implementado — depende das decisões do §4 item 14.
