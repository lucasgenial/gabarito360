# 068 — Plano e uso, integração financeira e legado — Casos de uso

**Natureza:** evolução de existente, reorientada em 12/11/2026 ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)). O legado de assinatura do MP-027 é documentado a partir do código. Plano e uso é uma área nova do Gabarito360, ainda não implementada.
**Perfis envolvidos:** professor autônomo; professor com vínculo institucional; a mesma pessoa com mais de um vínculo; representante institucional **[Pendente]** (o perfil não existe no documento 04; nenhum papel é deduzido de perfil pedagógico ou de direção); titular de assinatura legada; aplicativo externo (Central do cliente e administração financeira) e Mercado Pago (sistemas externos). O perfil interno `financeiro` não é ator de Plano e uso. Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md) §3 e §9.1.
**Atualizado em:** 2026-11-12

## O que mudou em 12/11/2026

O responsável reorientou a FN-068. Ela deixa de ser um módulo financeiro limitado. Ela passa a ser **Plano e uso**: a área que mostra, por vínculo, as informações autorizadas recebidas do aplicativo externo. A contratação e a gestão financeira ficam na Central do cliente, no aplicativo externo. O Gabarito360 não mantém catálogo comercial, não calcula preço e não é autoridade sobre saldo ou franquia. A decisão está no [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md). O ADR-0062 sucede em parte o [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) e preserva as regras válidas dele.

Onde está cada grupo de casos:

| Grupo | Casos | Situação |
|---|---|---|
| Legado do MP-027 | UC-068-01 a 04 | Descrevem o código atual. Nada é desligado sem tarefa própria (planejamento §6.3) |
| Contratos, faturas, gateway e catálogo locais | UC-068-05 a 10 | Superados em 12/11/2026 |
| Assunção financeira de escola autônoma | UC-068-11 | Mantido |
| Saldo e ação financeira no produto | UC-068-12 e 13 | Superados em 12/11/2026 (tabela abaixo) |
| Simulador e indisponibilidade da API | UC-068-14 e 15 | Mantidos |
| Jornadas de Plano e uso | UC-068-16 a 27 | Novos. Uma jornada por caso, de J1 a J12 |

O contrato de integração é o [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). O consumo da correção tem casos próprios na [FN-081](../081-creditos-correcao/casos-de-uso.md).

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto.

---

## Referências comuns aos casos 16 a 27

### Marcas

As marcas seguem o [14 §0](../../arquitetura/14-integracao-financeira.md): **[Diretriz]** (decisão do responsável), **[Histórico]**, **[Proposto]** e **[Pendente]**. Proposta de agente nunca vira diretriz. Nenhum caso abaixo está implementado.

### Meio de prova de cada critério

Cada critério traz o meio de prova entre parênteses. O mockup nunca fecha um critério marcado T ou E.

| Marca | Meio | Quem fecha |
|---|---|---|
| (M) | Conferência no mockup (subfase 2.3), por texto: tela, perfil, vínculo, situação, largura, tema, ação e resultado | `ui-ux`, em outro agente que não o autor, no navegador |
| (T) | Teste futuro na implementação | Etapa de implementação (subfase 2.4 e seguintes) |
| (R) | Revisão documental, inclusive busca textual | `documentacao` e `qualidade-testes` |
| (E) | Depende do aplicativo externo | Contrato 14 e o aplicativo externo, quando existir |

### Conceitos usados

Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos ([04](../../arquitetura/04-identidade-acesso-e-permissoes.md) e [glossário](../../glossario.md) são os donos dos termos). O extrato por vínculo não muda a titularidade contratual. O contrato pode ser da conta contratante da escola, e cada professor consulta pelo seu vínculo. **[Diretriz]**

- Uma ação **nova** usa o vínculo ativo.
- Uma ação **iniciada** mantém o contexto e o destinatário originais, mesmo depois de uma troca de vínculo.
- O destinatário aparece antes da confirmação externa.
- Não há consumo automático de outra conta nem transferência entre contextos.

### Estados (lista única, 14 estados)

Os casos usam esta lista pelo número. Estados diferentes não se fundem. Os textos são **[Proposto]** e passam por revisão do `ui-ux`. "Central do cliente" é nome de trabalho.

| N | Estado | Quando vale | Texto-chave (título → corpo) | Ação |
|---|---|---|---|---|
| 1 | Carregando | A consulta está em curso | "Consultando seu plano…" | — |
| 2 | Vazio (sem uso no período) | A consulta responde sem uso no período | "Sem uso neste período." → "Quando houver uso, ele aparece aqui." | Mudar o período |
| 3 | Sucesso | A consulta responde com dados | Valores com unidade, origem e hora da atualização | — |
| 4 | Acesso negado | O vínculo ou o perfil não permite a área | "Você não tem acesso a esta área." → "Se você tem outro vínculo, use o menu da conta para trocá-lo." | Voltar ao Painel |
| 5 | Serviço externo indisponível | Falha, tempo esgotado ou erro do aplicativo externo | "Não foi possível consultar o seu plano." → "Nenhum valor é mostrado até a próxima consulta. Provas, turmas e resultados continuam funcionando." | Tentar novamente |
| 6 | Sem cobertura no vínculo | A contratação ou o vínculo não cobre o recurso | "Este vínculo não tem cobertura para este recurso." → caminho permitido por perfil | Ação permitida |
| 7 | Sem saldo ou franquia disponível | O recurso é coberto, mas não há capacidade para a operação | "Não há disponibilidade para esta operação." → caminho permitido por perfil | Ação permitida |
| 8 | Limite atingido | Restrição individual ou institucional alcançada. **Não vale no uso livre sem decisão do responsável** (**[Pendente]**) | "O limite definido para este vínculo foi atingido." | Pedir ajuste, se permitido |
| 9 | Contratação em andamento | A pessoa está na passagem para a Central do cliente | "Você está na Central do cliente." → "Para: [destinatário]. Volte aqui para ver o resultado." | Retomar |
| 10 | Retorno pendente de confirmação | O retorno chegou sem confirmação externa | "Aguardando a confirmação da Central do cliente." → "Voltar da Central não confirma o pagamento." | Atualizar |
| 11 | Confirmado | A confirmação externa autenticada chegou | "Confirmado pela Central do cliente." → "A mudança aparece em [vínculo]." | Ver resumo |
| 12 | Cancelado | A pessoa ou a Central cancelou | "Contratação cancelada." → "Nada foi alterado." | Tentar de novo |
| 13 | Falha | A Central não concluiu | "A contratação não foi concluída." → "Veja o motivo na Central do cliente." | Abrir a Central |
| 14 | Solicitação de ajuste em análise | O pedido de ajuste foi aberto e aguarda a análise | "Solicitação em análise." → "A Central do cliente confirma o ajuste. O limite não muda até lá." | Ver situação |

Regras dos estados:

- Os estados 6, 7, 8, 4 e 5 são separados. Cada um tem texto e ação próprios.
- No uso livre, o estado 8 só existe se houver decisão. Hoje não há. **[Pendente]**
- O texto do estado 13 depende de a Central devolver o motivo. **[Pendente]**
- O estado 12 (cancelado) é ação do próprio usuário. O aviso usa `role="status"`, não `alert`. **[Proposto]**, a confirmar no mockup. O estado 13 usa `role="alert"`.

### Critérios comuns (CC)

Valem para todos os casos de 16 a 27, além dos critérios próprios de cada um.

- **CC-1.** Toda tela mostra o vínculo ativo em destaque (M).
- **CC-2.** Todo valor exibido traz unidade, origem e hora da atualização na mesma linha. Unidades de recursos diferentes nunca são somadas (M, T).
- **CC-3.** O Gabarito360 não calcula nem guarda saldo, franquia ou débito (T, R).
- **CC-4.** Nenhum texto trata como aprovada uma regra comercial: preço, pacote, franquia ou ciclo apresentados como decisão. A revisão procura essas regras, não a presença de números (R).
- **CC-5.** Todo dado fictício do mockup traz a marca "demonstração" (M).
- **CC-6.** A tela cobre as 11 larguras (320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px) e os dois temas, em viewport real. Há versão para celular e para desktop (M). Na subfase 2.4, a tela implementada repete a prova nas 11 larguras e nos 2 temas, por teste de navegador ou por conferência registrada por texto, sem prints (T, R; [ADR-0049](../../decisoes/0049-sem-prints-da-aplicacao.md)). A matriz evita redundância só nas combinações de perfil e estado.
- **CC-7.** Toda confirmação de negócio usa modal da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)) (M).
- **CC-8.** Nenhuma tela de Plano e uso edita limite, saldo ou alocação (M, T).

### Tela e rota

Não existe rota definida. Todos os casos usam a referência: **Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)**. O menu-alvo é o menu da conta, como entrada única ao fim da transição. O item legado "Assinatura e cobrança" convive até a decisão sobre o legado. Nenhuma rota, menu ou código muda nesta tarefa. **[Diretriz]**

### Lista de termos proibidos no uso livre

Nos textos de uso livre, os termos "dívida", "excedido", "devendo", "atraso" e "cobrança" não aparecem como consequência de passar a referência. A conferência é por busca textual (R, T).

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

**Regras de negócio:** [RN-GER-09](../../arquitetura/08-regras-de-negocio.md) (só o titular vê a assinatura). O legado usa a **conta de acesso** como titular (`titular_id` é o usuário). Isso é limitação do legado, não regra do modelo novo. No modelo novo, a titularidade contratual é da conta contratante, no aplicativo externo ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)).

**Critérios de aceite:**
- [x] Só a assinatura do próprio titular.
- [ ] ⚠ A assinatura está ligada a um usuário titular, não a um cliente. Os valores são `decimal`. Os estados são `trial`, `ativa`, `atrasada` e `cancelada`. Resolve: subfase 3.1 (destino do legado). O legado não vira o novo modelo.

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
| UC-068-09 | Cobrar a compra avulsa de créditos | Aplicativo financeiro externo. A ação humana tem identidade e permissão próprias; o Gabarito360 só oferece a entrada contextual (UC-068-19) |
| UC-068-10 | Publicar plano, preço e franquia no catálogo | Aplicativo financeiro externo (catálogo comercial) |

**Situação:** Superados. Não implementar no Gabarito360. Os números não são reutilizados.

---

## UC-068-11 — Efetivar a assunção financeira de uma escola autônoma

**Ator:** Financeiro **[Pendente]** (o papel futuro do perfil interno `financeiro` não está decidido), com Suporte ou Desenvolvedor; instância superior (rede) como parte
**Permissão:** a definir
**Escopo:** clientes e espaços envolvidos
**Pré-condições:** o responsável definiu as regras de alcance, vigência e período já pago; FN-029
**Pós-condições:** responsabilidade financeira transferida por fluxo auditável, com escolas e espaços delimitados
**Tela/rota:** a desenhar; não faz parte de Plano e uso

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

## UC-068-12 e UC-068-13 — Superados (12/11/2026)

Estes dois casos punham saldo, limites e ação financeira dentro do produto. A reorientação troca isso por Plano e uso, que mostra as informações autorizadas, e pela Central do cliente, que executa a ação. Os números não são reutilizados.

| Caso | Título anterior | Novo destino |
|---|---|---|
| UC-068-12 | Ver saldo, limites e histórico do contexto | UC-068-16 (autônomo), UC-068-17 (institucional), UC-068-18 (troca de vínculo), UC-068-25 (serviço indisponível), UC-068-26 (sem cobertura) e UC-068-27 (uso ligado a uma correção). A tela mostra disponibilidade e consumo autorizados. Ela não mostra saldo próprio |
| UC-068-13 | Ir a uma ação financeira (comprar créditos ou definir limites) | UC-068-19 (compra adicional), UC-068-20 (opções e mudança de plano), UC-068-21 (representante abre a Central), UC-068-22 (uso livre pede ajuste), UC-068-23 (capacidade limitada) e UC-068-24 (retorno). O Gabarito360 oferece só a entrada contextual. A ação acontece na Central do cliente, com identidade e permissão próprias |

As regras que continuam valendo vieram para os casos novos:

- Nenhuma credencial técnica do Gabarito360 compra créditos nem altera limites (teste de isolamento em UC-068-19).
- Um limite por professor não transfere crédito para a carteira pessoal. Renovar o limite não cria créditos.
- O formato da compra (D-4), o arrependimento e o reembolso seguem pendentes ([FN-081 §8](../081-creditos-correcao/planejamento.md) e planejamento §8).

**Situação:** Superados. Não implementar como descritos.

---

## UC-068-14 — Testar a correção de ponta a ponta com o simulador

**Ator:** desenvolvedor; testador na homologação; CI
**Permissão:** as permissões educacionais reais do usuário de teste
**Escopo:** desenvolvimento e homologação, com o simulador ativado no servidor
**Pré-condições:** subfase 1.1; simulador ativado por configuração explícita
**Pós-condições:** a correção fica real; o saldo, o extrato e os limites mudam só no estado fictício; nenhuma cobrança real nem dívida retroativa
**Tela/rota:** as telas reais do produto; Plano e uso (rota a definir na subfase 2.4) mostra a marca de simulação

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
**Tela/rota:** Leitor360, modo servidor (FN-079 UC-079-26)

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

# Jornadas de Plano e uso (UC-068-16 a UC-068-27)

Os casos abaixo cobrem as 12 jornadas da reorientação, de J1 a J12. Todos têm a mesma situação: **Não implementado — subfases 2.3 (mockup) e 2.4 (telas); depende de** Central do cliente e aplicativo externo (não existem), contrato 14 (proposto, protocolo pendente), cliente da API (subfase 1.2) e das decisões do planejamento §8.

Em todos os casos:

- A permissão de consulta de Plano e uso é **[Pendente]**. Este documento não cria chave de permissão. O servidor nega o que não puder confirmar.
- O aplicativo externo confirma o papel em cada ação que muda estado.
- O valor monetário do plano vigente é **[Pendente]**. Ele não é proibido nem exigido e não bloqueia a documentação.

---

## UC-068-16 — Consultar plano, disponibilidade e extrato pessoal (J1)

**Objetivo:** o professor autônomo entende o que cobre o seu acesso, o que ele ainda pode usar e o que já usou.
**Ator:** professor autônomo
**Permissão:** **[Pendente]**; a consulta vale só para o vínculo ativo
**Vínculo:** vínculo pessoal vigente
**Escopo:** espaço pessoal
**Pré-condições:** sessão ativa; vínculo pessoal vigente
**Pós-condições:** nenhum dado é gravado como plano, saldo ou débito
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. O professor abre o menu da conta e escolhe "Plano e uso".
2. A tela mostra o resumo: vínculo, plano vigente, recursos incluídos, ciclo, quantidades e percentuais aplicáveis, disponibilidade por recurso e operações em processamento.
3. O professor abre o extrato.
4. O professor escolhe o período (mês, ano ou intervalo) e os filtros em modal.
5. O professor abre o detalhe de uma linha em modal.

**Fluxos alternativos e de erro:**
- 2a. Consulta em curso: estado 1.
- 2b. Serviço externo indisponível: estado 5 (UC-068-25).
- 2c. Sem cobertura no vínculo: estado 6 (UC-068-26).
- 2d. Sem permissão: estado 4.
- 4a. Período sem uso: estado 2.

**Dados mínimos:** nome do vínculo; plano vigente; recursos incluídos; ciclo; quantidades e percentuais aplicáveis; disponibilidade; operações em processamento; período; tipo de evento; unidade; situação; hora da atualização.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2 e §4; [14](../../arquitetura/14-integracao-financeira.md) §3 e §5. Extrato: uma linha por operação, sem soma de unidades entre recursos.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] A tela mostra o plano vigente e as quantidades autorizadas. Ela não mostra catálogo, preço calculado nem saldo próprio (M, R).
- [ ] Perfil sem permissão: a API nega na resposta. Esconder a tela não basta (T).
- [ ] Com dois vínculos A e B, a resposta do vínculo A não contém nenhuma operação do B (T).
- [ ] Com falha da consulta, nenhum número aparece (M, T).
- [ ] Cada operação aparece em uma única linha, com a situação: em processamento, confirmado ou não cobrado. Reserva e confirmação nunca contam como dois débitos (M, T, E).
- [ ] Benefício ou crédito recebido é um evento próprio, sem sinal de débito (M, E).
- [ ] O extrato de uso não mostra compra, fatura nem pagamento. Eles estão na Central do cliente (M, R).
- [ ] Paginação e intervalo máximo do extrato seguem o contrato externo (T, E). **[Pendente]**

---

## UC-068-17 — Consultar cobertura e uso do vínculo institucional (J2)

**Objetivo:** o professor com vínculo institucional vê só a cobertura e o uso do seu vínculo.
**Ator:** professor com vínculo em escola
**Permissão:** **[Pendente]**
**Vínculo:** vínculo institucional ativo
**Escopo:** o próprio vínculo
**Pré-condições:** sessão ativa; vínculo institucional vigente
**Pós-condições:** nenhuma
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. O professor abre Plano e uso.
2. A tela mostra "Cobertura pela escola": modalidade (uso livre monitorado ou capacidade limitada), recursos incluídos e ciclo.
3. A tela mostra o uso do próprio professor no período.

**Fluxos alternativos e de erro:**
- 2a. A escola não cobre o recurso: estado 6.
- 2b. Recurso coberto, sem capacidade: estado 7.
- 2c. Limite individual atingido: estado 8. No uso livre, só com decisão (**[Pendente]**).
- 2d. Serviço externo indisponível: estado 5.
- 3a. Professor precisa de ajuste: "Pedir ajuste ao responsável" é texto de orientação. Quem recebe o pedido é **[Pendente]**.

**Dados mínimos:** nome da escola; modalidade; recursos; ciclo; uso do próprio professor; hora da atualização.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3 e §4. A modalidade vem do contrato, nunca da natureza (pública ou privada) da escola. O contrato da escola cobre as atividades daquela escola, não o trabalho pessoal.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] A tela não oferece compra pessoal como solução para atividade da escola (M, T).
- [ ] A resposta não contém dados de outros professores (T).
- [ ] O professor sem permissão aprovada nunca recebe o valor institucional. Isso vale para a resposta, não só para a tela (T). **[Pendente]** até a decisão de quem vê o resumo institucional.
- [ ] A modalidade exibida vem da informação do contrato, não do tipo de escola (E, R).
- [ ] No uso livre, a tela não mostra percentual sobre a referência nem indicador de esgotamento (M, R).

---

## UC-068-18 — Alternar vínculos sem misturar dados nem destinatários (J3)

**Objetivo:** a mesma pessoa troca de vínculo e vê só o que o vínculo ativo permite. Uma ação já iniciada não muda de destinatário.
**Ator:** pessoa com dois ou mais vínculos (por exemplo, autônomo e institucional)
**Permissão:** **[Pendente]**; a troca usa o menu da conta ([ADR-0030](../../decisoes/0030-previa-avatar-abre-o-menu-da-conta.md))
**Vínculo:** vínculo ativo antes e depois da troca
**Escopo:** o vínculo ativo
**Pré-condições:** dois vínculos ativos
**Pós-condições:** nada do vínculo anterior permanece; ações iniciadas mantêm o contexto original
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A pessoa abre Plano e uso no vínculo A.
2. A pessoa troca para o vínculo B pelo menu da conta. Plano e uso não duplica a troca.
3. A tela recarrega todos os blocos no vínculo B.

**Fluxos alternativos e de erro:**
- 1a. Existe uma ação iniciada em A (UC-068-19, 20, 22 ou 23). Depois da troca, a ação continua em A. O vínculo B não a herda.
- 3a. Uma aba antiga continua aberta em A: a próxima ação recebe a mensagem de contexto trocado (409), conforme o [04](../../arquitetura/04-identidade-acesso-e-permissoes.md) §4.3.
- 3b. Uma ação **nova** depois da troca usa o vínculo B.

**Dados mínimos:** vínculo ativo; vínculo e destinatário da ação iniciada.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §4 e condição B5. Não há consumo automático de outra conta nem transferência entre contextos.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Após a troca, nenhum texto, número ou linha do vínculo A permanece na tela (M, T; teste de navegador).
- [ ] O extrato e as entradas contextuais mostram só o vínculo ativo (M, T).
- [ ] A ação iniciada em A conserva A depois da troca para B (M como simulação; T como prova real).
- [ ] Uma ação nova depois da troca usa B (M, T).
- [ ] A aba antiga recebe aviso de contexto trocado, sem dados do outro vínculo (T).

---

## UC-068-19 — Iniciar compra adicional e retornar à atividade (J4)

**Objetivo:** o professor autônomo inicia a compra de adicionais a partir do contexto correto e volta à atividade de origem.
**Ator:** professor autônomo
**Permissão:** **[Pendente]** para a entrada contextual; o aplicativo externo autoriza a compra
**Vínculo:** vínculo pessoal ativo no momento do início
**Escopo:** espaço pessoal
**Pré-condições:** UC-068-16 concluído; vínculo pessoal; a Central do cliente existe (dependência externa)
**Pós-condições:** no Gabarito360, nada é registrado como compra. A intenção da travessia fica ligada a pessoa, vínculo, contexto de cobrança e tipo de ação
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4). Tela de passagem: tela inteira ou modal é **[Pendente]**

**Fluxo principal:**
1. O professor toca em "Comprar adicionais", em Plano e uso ou em um aviso contextual (por exemplo, na correção).
2. O servidor cria a intenção da travessia. O mecanismo é **[Pendente]**.
3. A passagem mostra o destinatário: "Para: seu plano pessoal". A confirmação antes de sair usa modal da aplicação.
4. O professor confirma. Estado 9.
5. O professor vai à Central do cliente e conclui a compra lá.
6. O professor volta ao Gabarito360 e retoma a atividade de origem.
7. A tela mostra o estado do retorno (UC-068-24).

**Fluxos alternativos e de erro:**
- 3a. O professor desiste na passagem: nada muda.
- 5a. O professor fecha a aba na Central: a intenção fica sem conclusão. A tela mostra estado 10 ou 12, conforme a informação externa.
- 6a. O retorno chega com outro vínculo ativo: aviso neutro, sem dados do outro contexto (UC-068-24).
- 2a. A emissão da travessia falha: estado 13, sem sair.

**Dados mínimos:** vínculo; recurso de origem (por exemplo, a correção); destino de retorno; tipo de ação.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §4 e condições B5 e B6. O destino de retorno vem de lista fechada por ambiente. A URL leva só uma correlação opaca. Nenhum segredo ou token em URL, log ou `Referer`. A emissão da travessia é auditada, sem token. Mecanismo e protocolo de identidade: **[Pendente]** ([14](../../arquitetura/14-integracao-financeira.md)). Uma ação financeira que muda estado exige identidade e permissão próprias no aplicativo externo.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] A passagem mostra o destinatário antes de sair (M).
- [ ] A intenção criada para o vínculo A conserva A depois da troca para B (M como simulação; T como prova real).
- [ ] Retorno sem confirmação externa mostra só "aguardando". O estado "confirmado" exige confirmação autenticada e idempotente (M, T, E).
- [ ] Destino de retorno fora da lista fechada é recusado. Parâmetro livre de URL é ignorado (T).
- [ ] A correlação de outra pessoa ou de outro vínculo é recusada (T).
- [ ] A credencial técnica do Gabarito360 não consegue comprar créditos nem alterar limites (T, teste de isolamento).
- [ ] A emissão da travessia fica na auditoria e não grava token (T).
- [ ] A formação da compra (D-4), o arrependimento e o reembolso seguem pendentes e não aparecem como regra (R).

---

## UC-068-20 — Ver opções de plano e mudar de plano (J5)

**Objetivo:** o professor autônomo vê o plano vigente e as quantidades autorizadas no Gabarito360. Para comparar ofertas e mudar de plano, ele vai à Central do cliente.
**Ator:** professor autônomo
**Permissão:** **[Pendente]** para a entrada contextual; o aplicativo externo autoriza a mudança
**Vínculo:** vínculo pessoal ativo no momento do início
**Escopo:** espaço pessoal
**Pré-condições:** UC-068-16 concluído; a Central do cliente existe (dependência externa)
**Pós-condições:** nenhum efeito no Gabarito360. A mudança, se houver, chega por confirmação externa
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A tela de Plano e uso **mostra** o plano vigente e as quantidades autorizadas.
2. O professor toca em "Ver opções de plano".
3. A passagem mostra o destinatário e o plano atual. Estado 9.
4. A Central do cliente compara as ofertas e contrata. O Gabarito360 não mantém catálogo e não calcula preço.
5. O professor volta e vê o estado do retorno (UC-068-24).

**Fluxos alternativos e de erro:**
- 1a. Autônomo sem plano atual: estado 6, com a ação "Ver opções de plano".
- 3a. Desistência na passagem: nada muda.
- 4a. A Central devolve falha ou cancelamento: estados 13 ou 12.

**Dados mínimos:** vínculo; plano vigente; destino de retorno.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §1 e §2. O valor monetário do plano vigente é **[Pendente]**. Preço, pacote, proporcionalidade e mudança de plano são pendências do responsável e da Central. O Gabarito360 não decide.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] A tela de Plano e uso mostra o plano vigente e as quantidades autorizadas (M).
- [ ] "Ver opções de plano" encaminha à Central. A tela não compara ofertas (M).
- [ ] Nenhum texto apresenta preço, pacote, franquia ou ciclo como regra aprovada. Dado fictício traz a marca "demonstração" (R, M).
- [ ] A exibição do valor monetário do plano vigente não é exigida nem proibida enquanto a decisão for pendente (R).
- [ ] A mudança de plano só aparece no Gabarito360 depois da confirmação externa (E, T).

---

## UC-068-21 — Representante institucional abre a Central do cliente (J6)

**Objetivo:** o representante institucional chega à Central do cliente, direto ou por acesso contextual, com validação externa.
**Ator:** representante institucional **[Pendente]** (perfil não definido; nenhum perfil de direção recebe o papel por dedução)
**Permissão:** **[Pendente]**; o aplicativo externo confirma o papel em cada ação
**Vínculo:** vínculo institucional, quando o acesso é contextual
**Escopo:** a instituição do vínculo
**Pré-condições:** a decisão sobre o perfil do representante; a Central do cliente existe (dependência externa)
**Pós-condições:** a Central valida o acesso. Nada é decidido no Gabarito360
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4); Central do cliente (aplicativo externo)

**Fluxo principal (a, contextual):**
1. O representante abre Plano e uso.
2. O representante toca em "Abrir a Central do cliente". A passagem nomeia a instituição. Estado 9.
3. A Central valida a identidade, o vínculo e o papel.

**Fluxo principal (b, direto):**
1. O representante entra na Central do cliente sem passar pelo Gabarito360 e sem usar funções docentes.

**Fluxos alternativos e de erro:**
- 2a. Pessoa sem o papel: o botão não aparece, ou a Central recusa (estado 4).
- 3a. A Central recusa: o Gabarito360 não contorna a recusa.
- 3b. Autenticação própria da Central antes de ações que mudam estado: protocolo de identidade **[Pendente]**.

**Dados mínimos:** vínculo; instituição; destino de retorno.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §1 e §4 e condições B2 e B6. "Quem vê o resumo institucional" e "quem abre a Central pela instituição" são **[Pendente]**.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] O representante entra na Central sem usar telas docentes (E).
- [ ] Nenhum papel financeiro é deduzido de perfil pedagógico ou de direção. O servidor nunca libera a área só porque o perfil é de direção (T, R).
- [ ] Nenhuma chave de permissão nova é criada por este caso (R).
- [ ] Esconder o botão no mockup não prova autorização no servidor (R).

**Nota de situação:** três verificações ficam **não verificáveis até a decisão** sobre o perfil do representante: botão visível só a quem tem o papel; resposta ao perfil sem o papel; resumo institucional por perfil.

---

## UC-068-22 — Acompanhar o uso livre e pedir ajuste (J7)

**Objetivo:** a instituição com uso livre monitorado acompanha o uso e pede ajuste, sem editar limites.
**Ator:** representante institucional **[Pendente]**; professor, para o próprio uso (UC-068-17)
**Permissão:** **[Pendente]**; só o Financeiro do aplicativo externo ajusta, após conferir o contrato
**Vínculo:** vínculo institucional da escola
**Escopo:** a instituição
**Pré-condições:** modalidade de uso livre monitorado no contrato; a Central do cliente existe (dependência externa)
**Pós-condições:** a solicitação fica em análise na Central. Nenhum limite muda no Gabarito360
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A tela mostra o uso no período e a referência de acompanhamento.
2. O representante toca em "Solicitar ajuste".
3. A passagem mostra a instituição como destinatário. Estado 9.
4. A solicitação é aberta na Central.
5. No retorno, a tela mostra "Solicitação em análise" (estado 14).

**Fluxos alternativos e de erro:**
- 2a. Pessoa sem permissão para solicitar: estado 4.
- 4a. Falha ou cancelamento: estados 13 ou 12.
- 5a. A análise conclui: estado 11, depois da confirmação externa.

**Dados mínimos:** instituição; período; uso; referência de acompanhamento; situação da solicitação; hora da atualização.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3. Referência de acompanhamento, limite bloqueante e cobertura contratual são conceitos diferentes. A escola não redistribui. O Gabarito360 não cria bloqueio nem indicador de esgotamento por inferência. O percentual não se aplica à referência de uso livre. Nada sugere dívida por ultrapassar a referência. O comportamento ao ultrapassar continua **[Pendente]**.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Perfil sem permissão: a API nega na resposta. Esconder a tela não basta (T).
- [ ] Nenhum campo permite editar limite (M, T).
- [ ] Nenhum percentual, barra de progresso ou indicador de esgotamento aparece sobre a referência (M, R).
- [ ] A resposta da API no uso livre não traz `percentual` nem "limite atingido" inferido (T).
- [ ] Nenhum termo da lista proibida do uso livre aparece (R, T).
- [ ] Todo número da referência traz a marca "demonstração" no mockup (M). Nenhum número aparece como regra aprovada (CC-4; R).
- [ ] O estado 8 (limite atingido) não existe para o uso livre enquanto não houver decisão (R, T).
- [ ] O ajuste só vale depois da conferência do contrato pelo Financeiro externo (E).

**Nota de situação:** os critérios de visibilidade e de permissão ficam **não verificáveis até a decisão** sobre o perfil do representante.

---

## UC-068-23 — Acessar a gestão externa de capacidade (J8)

**Objetivo:** a instituição com capacidade limitada abre a gestão de capacidade na Central. O efeito confirmado aparece depois no produto.
**Ator:** representante institucional **[Pendente]**; financeiro institucional autorizado, na Central
**Permissão:** **[Pendente]**; o aplicativo externo confirma o papel
**Vínculo:** vínculo institucional da escola
**Escopo:** a instituição
**Pré-condições:** modalidade de capacidade limitada no contrato; a Central do cliente existe (dependência externa)
**Pós-condições:** a disponibilidade só muda no Gabarito360 após a confirmação externa
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A tela mostra a disponibilidade da instituição, quando houver permissão, e o status resumido.
2. O representante toca em "Gerir capacidade na Central". A passagem mostra a instituição. Estado 9.
3. Na Central, o financeiro institucional autorizado administra a capacidade dentro do contratado e dos adicionais confirmados.
4. No retorno, a tela mostra o estado da tarefa (UC-068-24). Quando a confirmação externa chegar, a disponibilidade atualiza.

**Fluxos alternativos e de erro:**
- 1a. Sem capacidade para novos consumos: estado 7.
- 1b. Limite individual ou institucional alcançado: estado 8.
- 4a. Confirmação atrasada: estado 10.

**Dados mínimos:** instituição; disponibilidade; operações em processamento; situação da tarefa; hora da atualização.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3 e §4. A capacidade não passa do contratado. Consumo confirmado e reservas em processamento não são apagados. Distribuição, reservas, fim de ciclo e professor sem limite são **[Pendente]**.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Nenhuma tabela de alocação por professor existe no Gabarito360 (M, R).
- [ ] O texto diz que a capacidade só muda depois da confirmação externa (M).
- [ ] Depois do retorno, a disponibilidade exibida é a da última consulta externa, nunca a do retorno (T).
- [ ] Não se cria capacidade acima do contratado. Consumo confirmado e reservas não são apagados (E).

**Nota de situação:** os critérios de visibilidade e de permissão ficam **não verificáveis até a decisão** sobre o perfil do representante.

---

## UC-068-24 — Acompanhar o retorno comercial: pendente, confirmado, cancelado ou falha (J9)

**Objetivo:** a pessoa que volta da Central vê a situação honesta da tarefa. O retorno não prova pagamento.
**Ator:** quem iniciou a ação (autônomo, ou representante **[Pendente]**)
**Permissão:** **[Pendente]**; o aplicativo externo é a autoridade sobre pagamento e disponibilidade
**Vínculo:** o vínculo da tarefa iniciada
**Escopo:** a tarefa e seu destinatário
**Pré-condições:** UC-068-19, 20, 22 ou 23 iniciado
**Pós-condições:** a tela mostra uma situação por tarefa. Nada é registrado como pagamento no Gabarito360
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A pessoa volta da Central.
2. A API confere a correlação opaca contra a pessoa e o vínculo da sessão.
3. A tela mostra um aviso conforme a informação externa: retorno pendente (10), confirmado (11), cancelado (12) ou falha (13).
4. A pessoa retoma a atividade de origem.

**Fluxos alternativos e de erro:**
- 2a. Correlação de outra pessoa ou de outro vínculo: recusada, com aviso neutro e sem nomear nada.
- 2b. O retorno chega com outro vínculo ativo: aviso neutro. O vínculo não troca e nenhum dado do outro contexto aparece. O aviso só nomeia o vínculo da tarefa depois que a API confirma que a correlação é da mesma pessoa. Senão, vale o item 2a, sem nomear nada.
- 3a. O mesmo retorno chega duas vezes: um só aviso.
- 3b. A confirmação chega atrasada ou fora de ordem: a situação fica coerente com a última informação autenticada.
- 3c. A consulta externa falha: estado 5.

**Dados mínimos:** correlação opaca; vínculo da tarefa; tipo de ação; situação externa; hora da atualização.

**Regras de negócio:** condições B5 e B6 ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §5). A confirmação externa é autenticada, correlacionada e idempotente. Nenhum ID de URL autoriza nada. Mecanismo de notificação e de intenção: **[Pendente]**.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Retorno sem confirmação autenticada mostra só "aguardando" (M, T).
- [ ] Confirmação com assinatura inválida é recusada (T, E).
- [ ] Duas chegadas do mesmo retorno produzem um só aviso e um só efeito (T, E).
- [ ] Os estados 10, 11, 12 e 13 têm texto próprio (M).
- [ ] Retorno com outro vínculo ativo mostra aviso neutro, sem dados do outro contexto (M como simulação; T).
- [ ] Nenhum segredo ou token aparece em URL, log ou `Referer` (T).

---

## UC-068-25 — Tratar o serviço externo indisponível (J10)

**Objetivo:** a tela mostra um erro honesto. Ela não inventa saldo atual e não bloqueia o que é pedagógico.
**Ator:** qualquer pessoa que abre Plano e uso
**Permissão:** a mesma do caso de origem
**Vínculo:** vínculo ativo
**Escopo:** o vínculo ativo
**Pré-condições:** a consulta ao aplicativo externo falha, esgota o tempo ou retorna erro
**Pós-condições:** nenhum valor é mostrado como atual; nenhum simulador é ativado
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A pessoa abre Plano e uso. Estado 1.
2. A consulta falha.
3. A tela mostra o estado 5: "Não foi possível consultar o seu plano." e a ação "Tentar novamente".
4. A pessoa continua usando provas, turmas e resultados.

**Fluxos alternativos e de erro:**
- 3a. A nova tentativa funciona: estado 3.
- 3b. A nova tentativa falha: estado 5 de novo.
- 4a. Na tela de correção, a regra de consumo da [FN-081](../081-creditos-correcao/casos-de-uso.md) (UC-081-02 e UC-068-15) decide o envio ao modo servidor.

**Dados mínimos:** vínculo; hora da última tentativa. Nenhum valor anterior é exibido como atual.

**Regras de negócio:** [14](../../arquitetura/14-integracao-financeira.md) §10; [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2. A queda da API nunca ativa o simulador.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Com falha da consulta, nenhum valor guardado aparece como atual. A tela mostra "Indisponível" e "Tentar novamente" (M, T).
- [ ] O texto diz o que continua funcionando (M).
- [ ] Com o serviço externo fora, provas, turmas e resultados respondem normalmente (T).
- [ ] Nenhum caminho aciona o simulador (T).

---

## UC-068-26 — Explicar a falta de cobertura ou de disponibilidade (J11)

**Objetivo:** a pessoa sem contratação pessoal ou sem cobertura no vínculo entende a condição e o caminho permitido. O sistema não concede plano gratuito fictício.
**Ator:** professor autônomo sem plano; professor com vínculo sem cobertura; professor com cobertura sem disponibilidade
**Permissão:** **[Pendente]**
**Vínculo:** vínculo ativo
**Escopo:** o vínculo ativo
**Pré-condições:** a informação externa indica ausência de cobertura (estado 6) ou ausência de saldo ou franquia (estado 7)
**Pós-condições:** nenhuma
**Tela/rota:** Plano e uso (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A pessoa abre Plano e uso.
2. A tela mostra o estado 6 ("Este vínculo não tem cobertura para este recurso.") ou o estado 7 ("Não há disponibilidade para esta operação.").
3. A tela mostra o caminho permitido por perfil.

**Fluxos alternativos e de erro:**
- 3a. Autônomo: ação "Ver opções de plano" (UC-068-20) ou "Comprar adicionais" (UC-068-19), conforme o estado.
- 3b. Professor de escola: orientação para pedir ajuste ao responsável da escola. Nenhuma oferta pessoal como solução automática. Quem recebe o pedido é **[Pendente]**.
- 3c. Limite alcançado: estado 8, texto próprio.
- 3d. Sem permissão para ver: estado 4.

**Dados mínimos:** vínculo; tipo de condição (sem cobertura ou sem disponibilidade); recurso; hora da atualização.

**Regras de negócio:** [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2, §3 e §4. A falta de saldo ou de franquia bloqueia só o recurso tarifado (FN-081). O aparelho com capacidade continua corrigindo sem créditos.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Os estados 4, 5, 6, 7 e 8 têm textos e ações diferentes entre si (M).
- [ ] A tela não mostra oferta com preço zero nem rótulo de gratuito (M, R).
- [ ] O professor de escola não recebe oferta pessoal como solução automática para atividade da escola (M, T).
- [ ] O autônomo vê o caminho permitido ("Ver opções de plano" ou "Comprar adicionais") (M).
- [ ] No uso livre, a tela não sugere esgotamento nem usa o estado 8 sem decisão (M, R).

---

## UC-068-27 — Consultar o uso ligado a uma correção (J12)

**Objetivo:** a pessoa que vem da correção vê o consumo daquela operação. O reenvio aparece sem nova cobrança. O detalhe pedagógico só aparece quando autorizado.
**Ator:** professor ou quem corrige no modo servidor
**Permissão:** **[Pendente]** para o extrato. O detalhe de aluno e de aplicação exige uma permissão pedagógica **existente**, validada e demonstrada. A chave exata é **[Pendente]**
**Vínculo:** vínculo da operação
**Escopo:** a operação, dentro do escopo pedagógico do usuário
**Pré-condições:** o usuário vem da correção (Leitor360); a operação existe no aplicativo externo
**Pós-condições:** nenhuma
**Tela/rota:** Plano e uso, extrato filtrado (mockup na subfase 2.3; rota a definir na subfase 2.4)

**Fluxo principal:**
1. A pessoa abre o extrato filtrado pela correção.
2. A tela mostra uma linha por operação (`correction:{correction_subject_id}`), com a situação: em processamento, confirmado ou não cobrado.
3. Um reenvio do mesmo sujeito aparece como evento informativo "Reenvio sem nova cobrança".
4. A pessoa abre o detalhe. O Gabarito360 resolve aluno e aplicação no próprio banco, no momento do pedido, por `Escopo` e pela permissão demonstrada.

**Fluxos alternativos e de erro:**
- 4a. Sem a autorização local validada, ou na dúvida: o detalhe mostra "Dado restrito". A linha de consumo continua visível. O detalhe é negado.
- 2a. Serviço externo indisponível: estado 5.
- 2b. Sem uso no período: estado 2.

**Dados mínimos:** referência opaca da operação; situação; unidade; hora da atualização. O aplicativo externo não recebe nem devolve nome de aluno, resposta, nota ou outro detalhe pedagógico.

**Regras de negócio:** condição B4 ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §5); [14](../../arquitetura/14-integracao-financeira.md) §5; [FN-081](../081-creditos-correcao/planejamento.md) §6.5. Referências opacas limitam os dados pedagógicos. Elas não proíbem os dados de cobertura e consumo necessários. A identidade `correction_subject_id`, o reenvio sem cobrança nova e o consumo único por operação continuam valendo.

**Critérios de aceite:**
- [ ] CC-1 a CC-8 valem.
- [ ] Reserva e confirmação não somam dois débitos. Cada operação tem uma linha (M, T).
- [ ] O reenvio mostra "sem nova cobrança" (M). Existe no máximo um consumo definitivo por `correction_subject_id` (T, E; depende da identidade da submissão).
- [ ] A carga trocada com o aplicativo externo não contém nome de aluno, resposta nem nota. Só referência opaca e `correction_subject_id` (T).
- [ ] Sem a autorização local validada, o detalhe mostra "Dado restrito" na resposta, não só na tela (T). Nenhuma chave é escolhida por "parecer restritiva" (R).
- [ ] O perfil interno `financeiro` não vê aluno nem aplicação (T).
- [ ] Uma consulta em lote por página resolve aluno e aplicação, sem N+1 (T).
- [ ] A entidade de aplicação e o formato do `correction_subject_id` são **[Pendente]** e não são escolhidos aqui (R).

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-10 | Casos de uso criados a partir do código e do tronco original | lucasgenial |
| 2 | 2026-11-11 | Subfase 0.2 de adequação: citações aos documentos antigos trocadas pelos destinos do mapa; RN-015 passou a RN-GER-09 e as regras da escola autônoma a RN-EIN; UC-068-09 (compra avulsa de créditos) e UC-068-10 (catálogo) acrescentados; fronteira com a FN-081 declarada | lucasgenial |
| 3 | 2026-11-12 | Reorientação para o aplicativo financeiro externo: UC-068-05 a UC-068-10 superados; UC-068-11 ajustado; UC-068-12 a UC-068-15 acrescentados (experiência limitada, ação financeira, simulador e indisponibilidade) | lucasgenial |
| 4 | 2026-11-12 | UC-068-14 reescrito: teste de ponta a ponta com o simulador genérico, processamento real e estado fictício | lucasgenial |
| 5 | 2026-11-12 | Reorientação para Plano e uso (ADR-0062): título e perfis atualizados; UC-068-12 e UC-068-13 superados; UC-068-16 a UC-068-27 acrescentados (jornadas J1 a J12, com 14 estados, critérios comuns e meio de prova por critério); nota do legado em UC-068-02 (conta de acesso como titular) | lucasgenial |
| 6 | 2026-11-12 | Correções da revisão: estado 8 sem validade no uso livre sem decisão; estado 12 com `status` proposto; critérios de API para perfil sem permissão (UC-068-16 e 22) e para o uso livre (UC-068-22); notas de situação no lugar de pseudo-critérios (UC-068-21 a 23); UC-068-24 com a API conferindo a correlação; CC-6 com prova na subfase 2.4; UC-068-11 com ator Financeiro pendente; rótulos de conversa trocados por links ao ADR-0062 | lucasgenial |
