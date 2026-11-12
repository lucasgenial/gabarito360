# 068 — Financeiro (experiência limitada, integração e legado) — Casos de uso

**Natureza:** evolução de existente (o legado de assinatura do MP-027 é documentado a partir do código)
**Perfis envolvidos:** usuário com acesso ao contexto financeiro (pessoal, escola ou núcleo); responsável financeiro institucional; Financeiro, Desenvolvedor e Suporte (contexto interno); titular de assinatura legada; aplicativo financeiro e Mercado Pago (sistemas externos). Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1.
**Atualizado em:** 2026-11-12

Os casos 01 a 04 descrevem o legado do MP-027 como está no código. Nada neles é desligado sem tarefa própria (planejamento §6.3).

Os casos 05 a 10 foram superados em 12/11/2026. Eles punham contratos, faturas, gateway, compra de créditos e catálogo no Gabarito360. Essa direção é do aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)). Os números não são reutilizados. O texto anterior está no commit `ca6109d5` do PR #274.

Os casos 12 a 15 descrevem a experiência financeira limitada e a integração. O contrato é o [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). O consumo da correção tem casos próprios na [FN-081](../081-creditos-correcao/casos-de-uso.md).

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
- [ ] ⚠ As páginas de retorno e os textos de checkout permanecem. Resolve: subfase 3.1 (destino do legado), em tarefa própria.

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
- [ ] ⚠ A assinatura está ligada a um usuário titular, não a um cliente. Os valores são `decimal`. Os estados são `trial`, `ativa`, `atrasada` e `cancelada`. Resolve: subfase 3.1 (destino do legado). O legado não vira o novo financeiro.

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
- [ ] ⚠ A chamada ao `MercadoPagoService` é direta, sem interface de gateway, fila ou auditoria. Resolve: subfase 3.1 (destino do legado).

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
- [ ] ⚠ O processamento ocorre dentro da requisição, sem fila. A rota está ativa, embora não haja checkout. Resolve: subfase 3.1 (destino do legado). Não desligar sem autorização.

**Situação:** Implementado (legado) — evidência: `WebhookController`, `MercadoPagoService::validarWebhook`

---

## UC-068-05 a UC-068-10 — Superados (12/11/2026)

| Caso | Título anterior | Novo destino |
|---|---|---|
| UC-068-05 | Registrar contrato de um cliente | Aplicativo financeiro externo. ⚠ Interpretação a confirmar (planejamento §8) |
| UC-068-06 | Emitir fatura e registrar pagamento manual | Aplicativo financeiro externo. ⚠ Interpretação a confirmar (planejamento §8) |
| UC-068-07 | Acompanhar recebimentos e inadimplência | Aplicativo financeiro externo. A regra "nenhum bloqueio sem regra aprovada" continua (planejamento §8) |
| UC-068-08 | Cobrar por um provedor de pagamento | Aplicativo financeiro externo (meios de pagamento) |
| UC-068-09 | Cobrar a compra avulsa de créditos | Aplicativo financeiro externo. A ação humana tem identidade e permissão próprias (UC-068-13) |
| UC-068-10 | Publicar plano, preço e franquia no catálogo | Aplicativo financeiro externo (catálogo comercial) |

**Situação:** Superados. Não implementar no Gabarito360.

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
2. O fato financeiro da assunção é registrado no aplicativo financeiro. O Gabarito360 recebe a confirmação pela integração e audita a mudança de acesso.
3. Só então a autorização de acesso prevista na [RN-EIN-07](../../arquitetura/08-regras-de-negocio.md) pode ser concedida (FN-029).

**Fluxos alternativos e de erro:**
- 1a. Associação ao catálogo, contrato da rede com outras escolas ou pagamento avulso não efetivam a assunção.

**Regras de negócio:** [RN-EIN-07 e RN-EIN-08](../../arquitetura/08-regras-de-negocio.md).

**Critérios de aceite:**
- [ ] Nenhum acesso é ampliado sem assunção efetivada e auditada.
- [ ] Dados, autoria e documentos emitidos são preservados.
- [ ] ⚠ A RN-EIN-08 ("o sistema não devolve valores já pagos") vale neste fluxo. Ela não vale como regra do novo billing. O conflito com a pendência jurídica 1 está registrado no planejamento §8.

- [ ] ⚠ Como o Gabarito360 recebe a confirmação da assunção é pendência do contrato ([14](../../arquitetura/14-integracao-financeira.md) §12).

**Situação:** Não implementado — depende das decisões do §8.

---

## UC-068-12 — Ver saldo, limites e histórico do contexto

**Ator:** usuário com acesso ao contexto financeiro; responsável financeiro institucional
**Permissão:** a definir (planejamento §8); o aplicativo financeiro confere a permissão
**Escopo:** só o contexto do vínculo ativo (pessoal, escola ou núcleo)
**Pré-condições:** subfases 1.1 e 2.1 entregues
**Pós-condições:** nenhuma; nada é gravado como saldo no Gabarito360
**Tela/rota:** `mockups/financeiro.html`, telas 1 (Visão) e 2 (Histórico), por perfil e situação; mockup em revisão (subfase 2.0). Rota real a definir na subfase 2.1

**Fluxo principal:**
1. O usuário abre a área financeira do contexto.
2. O Gabarito360 consulta o aplicativo financeiro pela porta `BillingClient`.
3. A tela mostra o saldo, os limites e o histórico recebidos, com o momento da consulta.

**Fluxos alternativos e de erro:**
- 2a. O aplicativo financeiro não responde: a tela mostra o estado de erro, sem saldo calculado localmente.
- 2b. O aplicativo financeiro recusa a consulta: a tela informa que o usuário não tem acesso a esse contexto.
- 3a. Professor com limite institucional: ele vê o próprio limite e o consumo dele. Ele não vê o saldo da instituição sem permissão própria.

**Regras de negócio:** contrato [14](../../arquitetura/14-integracao-financeira.md) §1, §4 e §7.

**Critérios de aceite:**
- [ ] O Gabarito360 não calcula nem guarda saldo.
- [ ] O usuário não vê o contexto de outra pessoa ou de outra instituição.
- [ ] O perfil Financeiro interno não vê nomes de alunos.

**Situação:** Não implementado — subfases 1.1, 2.0 e 2.1.

---

## UC-068-13 — Ir a uma ação financeira (comprar créditos ou definir limites)

**Ator:** responsável financeiro do contexto
**Permissão:** identidade e permissão próprias no aplicativo financeiro
**Escopo:** contexto do vínculo ativo
**Pré-condições:** decidido o lugar das ações humanas (planejamento §8)
**Pós-condições:** a ação acontece no aplicativo financeiro; o Gabarito360 só mostra o resultado
**Tela/rota:** `mockups/financeiro.html`: botões "Comprar créditos" e "Ajustar limite" abrem o modal de encaminhamento "Abrir aplicativo financeiro". O lugar real da ação continua pendente

**Fluxo principal:**
1. O responsável escolhe comprar créditos para a entidade ou ajustar o limite mensal de um professor.
2. O Gabarito360 encaminha a ação ao aplicativo financeiro, sem usar a credencial técnica dele.
3. O aplicativo financeiro valida a identidade, o vínculo e a permissão, e executa a ação.

**Fluxos alternativos e de erro:**
- 3a. Sem permissão financeira: o aplicativo financeiro recusa. O Gabarito360 não contorna a recusa.

**Regras de negócio:** contrato [14](../../arquitetura/14-integracao-financeira.md) §4 e §8. O limite por professor não transfere crédito para a carteira pessoal. Renovar o limite não cria créditos.

**Critérios de aceite:**
- [ ] A credencial técnica do Gabarito360 não consegue comprar créditos nem alterar limites (teste de isolamento).
- [ ] ⚠ O formato da compra (D-4), o arrependimento e o reembolso dependem das pendências da [FN-081 §8](../081-creditos-correcao/planejamento.md) e do planejamento §8.

**Situação:** Não implementado — depende das decisões do §8.

---

## UC-068-14 — Testar a correção de ponta a ponta com o simulador

**Ator:** desenvolvedor; testador na homologação; CI
**Permissão:** as permissões educacionais reais do usuário de teste
**Escopo:** desenvolvimento e homologação, com o simulador ativado no servidor
**Pré-condições:** subfase 1.1; simulador ativado por configuração explícita
**Pós-condições:** a correção fica real; o saldo, o extrato e os limites mudam só no estado fictício; nenhuma cobrança real nem dívida retroativa
**Tela/rota:** as telas reais do produto; a tela financeira mostra a marca de simulação

**Fluxo principal:**
1. O testador escolhe um cenário do contrato §9.2 e, se precisar, reinicia o estado fictício.
2. O testador entra com um usuário real de teste e envia uma submissão ao modo servidor.
3. O Gabarito360 pede a autorização à porta. O simulador responde conforme o cenário.
4. A submissão segue a fila e o processamento reais até `READY_FOR_REVIEW`.
5. O Gabarito360 pede o `COMMIT`. O simulador consome no estado fictício.
6. O testador confere a correção e vê o saldo e o extrato fictícios, marcados como simulação.

**Fluxos alternativos e de erro:**
- 3a. Cenário sem saldo, limite atingido ou permissão recusada: o envio é recusado antes da fila.
- 3b. Cenário de timeout ou indisponibilidade: o envio é recusado (UC-068-15).
- 4a. A submissão falha: o Gabarito360 pede o `RELEASE`, e o estado fictício devolve a reserva.
- 2a. Reenvio da mesma submissão: o simulador reconhece a repetição e não consome de novo.

**Regras de negócio:** contrato [14](../../arquitetura/14-integracao-financeira.md) §9.

**Critérios de aceite:**
- [ ] O processamento, a fila, a leitura e a conferência são os reais. A correção não é simulada.
- [ ] O saldo e o extrato fictícios mudam de forma coerente com reserva, `COMMIT`, `RELEASE` e repetição.
- [ ] O estado fictício fica separado das tabelas do produto e pode ser reiniciado.
- [ ] O simulador não tem carteira, ledger nem motor financeiro.
- [ ] O simulador não muda nenhuma permissão educacional.
- [ ] Um consumo simulado nunca vira cobrança real nem é migrado.
- [ ] Sem ativação explícita, ou em produção, o simulador não responde.

**Situação:** Não implementado — subfase 1.1.

---

## UC-068-15 — Recusar o consumo quando a API financeira está indisponível

**Ator:** sistema
**Permissão:** não se aplica
**Escopo:** envio ao modo servidor
**Pré-condições:** o cliente da API real está configurado; o aplicativo financeiro não responde
**Pós-condições:** nenhum consumo autorizado sem resposta válida
**Tela/rota:** Leitor360, modo servidor (FN-079 UC-079-26); `mockups/financeiro.html`, tela 4, situação "Aplicativo financeiro indisponível"

**Fluxo principal:**
1. O Gabarito360 pede autorização e não recebe resposta no prazo.
2. O Gabarito360 repete com a mesma referência, dentro do limite configurado.
3. Sem resposta válida, o envio ao modo servidor é recusado. A leitura no aparelho e a revisão manual continuam.

**Fluxos alternativos e de erro:**
- 1a. Existe concessão válida para contingência: segue o protocolo aprovado. ⚠ O protocolo está pendente (contrato §10).

**Regras de negócio:** [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §6; contrato [14](../../arquitetura/14-integracao-financeira.md) §10.

**Critérios de aceite:**
- [ ] A falha da API não ativa o simulador (teste).
- [ ] A falha da API não autoriza o consumo (teste).
- [ ] As funções pedagógicas continuam disponíveis.

**Situação:** Não implementado — subfases 1.1 e 1.2.

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-10 | Casos de uso criados a partir do código e do tronco original | lucasgenial |
| 2 | 2026-11-11 | Subfase 0.2 de adequação: citações aos documentos antigos trocadas pelos destinos do mapa; RN-015 passou a RN-GER-09 e as regras da escola autônoma a RN-EIN; UC-068-09 (compra avulsa de créditos) e UC-068-10 (catálogo) acrescentados; fronteira com a FN-081 declarada | lucasgenial |
| 3 | 2026-11-12 | Reorientação para o aplicativo financeiro externo: UC-068-05 a UC-068-10 superados; UC-068-11 ajustado; UC-068-12 a UC-068-15 acrescentados (experiência limitada, ação financeira, simulador e indisponibilidade) | lucasgenial |
| 4 | 2026-11-12 | UC-068-14 reescrito: teste de ponta a ponta com o simulador genérico, processamento real e estado fictício | lucasgenial |
| 5 | 2026-11-12 | UC-068-12, UC-068-13 e UC-068-15 ligados ao mockup funcional `mockups/financeiro.html` (subfase 2.0, em revisão) | lucasgenial |
