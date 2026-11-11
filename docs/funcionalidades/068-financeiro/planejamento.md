# 068 — Financeiro (contratos, faturas, pagamentos) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 17
**Posição no plano mestre:** Onda 4 (proposta do §5 do [plano mestre](../../planejamento/00-plano-mestre.md)): 068 financeiro, depois 069 selo; depende de decisão comercial
**Natureza:** evolução de existente (o legado de assinatura do MP-027 está ativo no código e é documentado a partir dele)
**Origem:** tronco original — pacote financeiro F1–F3 da Fase 5, regras comerciais da escola autônoma e a reconciliação FIN. Os documentos antigos (`docs/28`, `docs/163`, `docs/164`, `docs/176` e `docs/06`) foram removidos; os destinos estão no [mapa dos documentos antigos](../../historico/mapa-documentos-antigos.md). Decisões de billing de 11/11/2026: [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)
**Atualizado em:** 2026-11-11, por lucasgenial

> Escreva este documento no padrão ASD-STE100 moderado (AGENTS.md): uma ideia por frase, voz ativa, frases curtas.

Esta funcionalidade é **dona de planos, assinaturas, pagamentos, contratos e faturas**, e da conciliação desses itens com o billing. O consumo de créditos e as regras da correção são da [FN-081](../081-creditos-correcao/planejamento.md). A arquitetura de billing é do [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md).

## 1. Objetivo

Dar à equipe do Gabarito360 (perfil Financeiro) o controle comercial de cada cliente: contrato, itens, faturas, cobranças, pagamentos e notas fiscais. O lançamento é manual desde o início. A arquitetura aceita vários meios de pagamento depois. O Financeiro gerencia um cliente sem acessar dados de alunos.

## 2. Escopo

- **Inclui:**
  - **Adequação aos princípios** (subfase 0.2): revalidação contra os ADRs vigentes, antes de qualquer código.
  - **F1 — modelo comercial e fiscal:** tabelas `contratos`, `contrato_itens`, `faturas`, `cobrancas`, `pagamentos`, `notas_fiscais` e `eventos_pagamento`; migração de `planos`, `assinaturas` e `pagamentos` do MP-027 para o esquema definitivo, trocando titular por cliente; valores em centavos; cliente público (processo, empenho, vigência, nota fiscal); desconto, cortesia e modalidade `presenteado`.
  - **Catálogo comercial:** produtos, recursos, planos, preços e entitlements (franquias do plano). Nenhum preço ou regra comercial fica fixo no código (ADR-0061).
  - **Assinaturas:** estados `trialing`, `active`, `past_due`, `suspended`, `canceled` e `expired`. `past_due` não implica bloqueio total imediato.
  - **Conciliação com o billing:** o catálogo e a assinatura desta FN alimentam a franquia e a carteira que a FN-081 consome pela porta `BillingClient`. A conciliação não duplica tabelas (§6.3).
  - **F2 — telas do Financeiro:** contratos, faturas, baixa manual e inadimplência no cadastro do cliente, e painel de recebimentos. O bloqueio por inadimplência só existe depois de regra aprovada.
  - **F3 — interface de pagamentos:** `GatewayPagamento` (criar cobrança, consultar, cancelar, estornar, validar webhook), `GatewayManual`, adaptação do `MercadoPagoService`, webhook único por provedor com assinatura verificada, eventos pela fila sem repetir efeito.
  - **Cobrança da compra avulsa de créditos** pedida pela FN-081. A FN-068 cobra; a FN-081 credita a carteira depois do evento `payment_confirmed`.
  - Regras comerciais da escola autônoma: assunção financeira pela instância superior por fluxo auditável, alcance e vigência da assunção, período já pago e encerramento posterior ([RN-EIN-07 e RN-EIN-08](../../arquitetura/08-regras-de-negocio.md)).
  - Preços dos planos ([RN-GER-09](../../arquitetura/08-regras-de-negocio.md), Substituída) e condições de lançamento comercial ([09 Interface e design system](../../arquitetura/09-interface-e-design-system.md) §2.3), conforme o §8.
- **Não inclui:**
  - Arquitetura de billing, porta `BillingClient`, adaptadores, esquema `billing_` e contingência → [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md).
  - Consumo de créditos, reserva, `COMMIT`, `RELEASE`, extrato de consumo e créditos por contribuição validada → [FN-081](../081-creditos-correcao/planejamento.md).
  - Cadastro de clientes e `contrato_referencia` em texto → [FN-023](../023-cadastro-clientes/planejamento.md) (concluída).
  - Autocadastro, checkout público e MP-029 → [FN-030](../030-saas-autocadastro-assinatura/planejamento.md) (substituída em parte; ver §8).
  - Selo dos documentos por modalidade → [FN-069](../069-selo-documentos/planejamento.md).
  - Adesão da escola autônoma → [FN-029](../029-escola-independente/planejamento.md); encerramento de cliente → [FN-028](../028-encerramento-cliente/planejamento.md).
  - E-mail `assinatura-pagamento` → [FN-060](../060-email-transacional/planejamento.md).
  - M3 Core, gateways de produção e NFSe → fora do escopo do projeto nesta fase (ADR-0061).

**Regra fixa:** nunca guardar número de cartão ou CVV. Só tokens e identificadores do provedor.

## 3. Fontes de verdade

A ordem das fontes é a do [ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md).

1. **Mockup aprovado:** não existe mockup das telas do Financeiro. Ele é obrigatório antes da F2 (subfase 2.0).
2. **App atual:** as views, os componentes e o CSS de `apps/web` na `main`. As telas do contexto interno de `clientes/*` (FN-023) são o ponto de partida.
3. **Documentação:**
   - [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) (arquitetura de billing) e [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md) item 8 (identidade da cobrança da correção).
   - [08 Regras de negócio](../../arquitetura/08-regras-de-negocio.md): RN-GER-09 (Substituída; assinatura e checkout) e RN-EIN-01 a RN-EIN-10 (escola autônoma).
   - [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1: perfil Financeiro e contexto interno.
   - [03 Modelo de dados](../../arquitetura/03-modelo-de-dados.md): tabelas comerciais existentes e o padrão append-only.
   - [09 Interface e design system](../../arquitetura/09-interface-e-design-system.md): §2.3 (condições de lançamento comercial) e §11 (inventário das telas; as telas SaaS constam como novas, sem mockup).
   - [Operação](../../operacao/README.md): publicação, backup e incidentes.
4. **Design system:** `style-system/` (tokens, componentes e faixas por aparelho).
5. **Históricos:** `mockups/` e `style-system/historico/`. Eles não são referência visual.

Registro histórico da sessão de decisão de 11/11/2026: [Decisões de billing, créditos e M3 Core](../../historico/2026-11-11-decisoes-billing.md). Ele não é norma.

Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| ADR-0061 billing | Define a fronteira entre o comercial (esta FN) e o consumo (FN-081) | Aceita |
| Decisão comercial (§8) | Preços, franquias por plano, autocadastro e condições de lançamento | Pendente |
| FN-021 clientes-espacos | O contrato pertence ao cliente | Concluída |
| FN-023 cadastro-clientes | As telas do Financeiro ficam no cadastro do cliente | Concluída |
| FN-013 acessos-internos | Perfil Financeiro e contexto interno | Concluída |
| FN-004 auditoria | Trilha das operações financeiras | Parcial |
| FN-002 base-operacao | Eventos de pagamento pela fila | Concluída |
| FN-074 tabelas-padrao | Padrão das listas das telas do Financeiro | Em homologação |

**Funcionalidades que dependem desta:** FN-081 (franquia do plano e cobrança da compra avulsa), FN-069 (selo), FN-029 (assunção financeira), FN-028 (encerramento), FN-023 (contratos completos) e FN-030 (preços).

## 5. Fases e subfases

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (MP-027) Legado de assinatura | Tabelas `planos`, `assinaturas` e `pagamentos`; `MercadoPagoService`; `GET /planos`; `assinaturas/minha` e `cancelar`; webhook do Mercado Pago; checkout desativado (409, contratação por contato) | — | Substituída em parte (código ativo) | commit de68d3f; PR #59; `AssinaturaController`, `WebhookController`, `ContratacaoContatoTest` |
| 0.2 Adequação aos princípios | Revalidação deste planejamento e dos casos de uso contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057, ADR-0058 e ADR-0061; cabeçalho no modelo atual; citações aos documentos antigos trocadas pelos destinos do mapa; fronteira com a FN-081 declarada | O campo "Conforme princípios" sobe para a versão vigente e a FN sai de `04-adequacoes.md` | Entregue (documentação) | Este PR |
| 1.1 (F1) Modelo comercial e fiscal | Migrations, models, migração do legado, centavos, cliente público, cortesia, catálogo e estados de assinatura | Os testes cobrem contrato, fatura e pagamento manual; a conciliação do §6.3 não duplica tabela | Pendente | — |
| 2.0 (F2) Mockup das telas do Financeiro | Mockup sobre a casca, os componentes e o CSS reais de `apps/web`, em todas as faixas do ADR-0057 | O responsável aprova o mockup; a revisão de UI/UX é de outro agente (ADR-0020) | Pendente | — |
| 2.1 (F2) Telas do Financeiro | Contratos, faturas, baixa manual, inadimplência e painel de recebimentos | O Financeiro gerencia um cliente sem acesso a dados de alunos; conferência por texto em todas as larguras de referência | Pendente | — |
| 3.1 (F3) Interface de pagamentos | `GatewayPagamento`, `GatewayManual`, adaptação do Mercado Pago, webhook por provedor e fila idempotente | Um provedor falso de teste passa por todo o fluxo | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)

### 6.1 Banco e migração

- A API é dona do banco. As migrations são aditivas.
- A migração de `assinaturas` e `pagamentos` muda tipo e titularidade. Hoje os valores são `decimal(10,2)` e os vínculos são `titular_id`, `rede_id` e `secretaria_id`. A migração para cliente e para centavos exige prévia e reversão ensaiadas.
- Os registros financeiros e de consumo são append-only, no padrão do projeto ([03 Modelo de dados](../../arquitetura/03-modelo-de-dados.md), item 6). Um estorno é um lançamento novo. Nada é apagado.

### 6.2 Autorização e dados

- Somente contexto interno (`ContextoInterno`). Hoje o Financeiro usa `cliente.ver` e `cliente.gerir`. As chaves financeiras novas precisam de aprovação.
- Nenhuma rota financeira expõe dado pedagógico. O Financeiro vê totais de consumo, nunca nomes de alunos (regra espelhada na [FN-081 §6.5](../081-creditos-correcao/planejamento.md)).
- Webhooks: assinatura verificada, idempotência por identificador do evento e processamento pela fila.
- Sem dados de cartão. Só tokens e identificadores do provedor.
- Auditoria de toda baixa, estorno, cortesia e assunção financeira.
- Volume baixo (meta de 100 instituições). As listas são paginadas.

### 6.3 Conciliação do legado com o esquema `billing_`

O esquema `billing_` do [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §5 nasce definitivo. Ele **não cria uma segunda base comercial**. A tabela de cada conceito existe uma só vez. Nomes equivalentes são aceitos.

| Conceito | Hoje no código (MP-027) | Destino | Dono |
|---|---|---|---|
| Catálogo de produtos e recursos | não existe | `products`, `resources` | FN-068 |
| Plano | `planos` (com preço embutido, `decimal`) | `plans`, com o preço movido para `prices` em centavos | FN-068 |
| Franquia do plano | `planos.limites` (JSON) | `plan_entitlements/allowances` | FN-068 |
| Assinatura | `assinaturas` (`titular_id`, `rede_id`, `secretaria_id`; estados `trial`, `ativa`, `atrasada`, `cancelada`) | `subscriptions`, por cliente e contexto de cobrança; estados `trialing`, `active`, `past_due`, `suspended`, `canceled`, `expired` | FN-068 |
| Pagamento | `pagamentos` (`decimal`, `gateway_payment_id`) | `pagamentos` em centavos, mais `cobrancas`, `faturas`, `notas_fiscais` e `eventos_pagamento` | FN-068 |
| Franquia concedida no ciclo | não existe | `billing_allowances` | FN-081, pela porta |
| Carteira, lotes e ledger | não existe | `billing_wallets`, `billing_wallet_lots`, `billing_ledger_entries` | FN-081, pela porta |
| Operação, reserva e consumo | não existe | `billing_operations`, `billing_reservations`, `billing_usage_events` | FN-081, pela porta |
| Lease de contingência | não existe | `billing_contingency_leases` | FN-081, pela porta |

Regras da conciliação:

1. `planos`, `assinaturas` e `pagamentos` **migram** para o esquema definitivo. O sistema não cria tabelas paralelas com o mesmo conceito.
2. Billing não é Payments (ADR-0061 §2). A tabela `pagamentos` continua na camada de meios de pagamento. Ela não vira ledger de créditos.
3. A compra de créditos segue este caminho: a FN-068 gera a cobrança; o gateway confirma com `payment_confirmed`; a FN-081 concede o lote `PURCHASED` na carteira. A FN-068 nunca escreve em tabela `billing_`.
4. A FN-068 não lê nem escreve as tabelas `billing_` de consumo. Ela consulta saldo e franquia pela porta `BillingClient`, quando precisar.
5. Nenhum preço, franquia ou regra comercial crítica fica fixo no código.
6. ⚠ Qual componente é a fonte da verdade do esquema `billing_` durante a transição, e depois do M3 Core, é decisão pendente (§8).

## 7. Interface

O app é responsivo ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)). As telas do Financeiro atendem a **todas as faixas**: celular pequeno, celular, tablet (retrato e paisagem), notebook, desktop e telas grandes até 4K, com regras próprias para toque.

- Confira cada tela nas larguras 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro.
- Use os cortes canônicos do ADR-0057 (400, 720/721, 767/768, 1023/1024, 1099/1100, 1279/1280 px e altura 699/700 px), `pointer: coarse` e consultas de contêiner. Um corte novo exige registro no design system.
- A referência visual é o app atual: as views, os componentes e o CSS de `apps/web` ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md)). O design system é `style-system/`. Os mockups antigos são históricos.
- As telas ficam no contexto interno, junto de `clientes/*`. As tabelas seguem o padrão da [FN-074](../074-tabelas-padrao/planejamento.md), com a coluna Ações à direita ([ADR-0039](../../decisoes/0039-coluna-de-acoes-a-direita-e-ordenacao.md)).
- Estados obrigatórios em cada tela: carregando, vazio e erro.
- Confirmações usam modal da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)). O JavaScript fica em arquivo externo ([ADR-0023](../../decisoes/0023-javascript-em-arquivos-externos.md)).
- **O mockup vem antes da F2** (subfase 2.0). A revisão de UI/UX é de outro agente ([ADR-0020](../../decisoes/0020-revisao-de-ui-ux-por-especialista.md)). O implementador não aprova a própria tela.
- A conferência é registrada por texto, sem prints ([ADR-0049](../../decisoes/0049-sem-prints-da-aplicacao.md)).

## 8. Riscos e decisões pendentes do responsável

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| **Fonte da verdade do esquema `billing_`** na transição e depois do M3 Core | (a) O Gabarito360 é dono do esquema até o Core existir; o Core adota o esquema já validado; (b) o M3 Core define o esquema desde já, e o Gabarito360 implementa uma cópia; (c) um contrato de esquema versionado, fora dos dois produtos, é a fonte, e os dois o implementam | (a) agora, com o esquema declarado num contrato versionado junto do ADR-0061. Quando o Core nascer, a fonte passa a ele por migração registrada. O Core não existe e não pode bloquear o Gabarito360 | **Pendente** |
| **Conflito: "sem devolução de valores já pagos" × pendência jurídica 1** (arrependimento, CDC art. 49, reembolso e pacote parcialmente consumido) | (a) [RN-EIN-08](../../arquitetura/08-regras-de-negocio.md) continua valendo só no escopo original (transição de responsabilidade financeira entre instituições) e não alcança a compra de créditos, que segue a revisão jurídica; (b) estender "sem devolução" também à compra de créditos; (c) suspender a RN-EIN-08 até a revisão jurídica | (a). A RN-EIN-08 nasceu para a assunção financeira entre instituições. A compra de créditos por pessoa pode atrair o CDC art. 49. **Conflito registrado até a revisão jurídica.** A regra "sem devolução" não vale como regra do novo billing | **Pendente** |
| **Pendência técnica: entidade única de aluno + aplicação** (`correction_subject_id`, [FN-081 §6.1](../081-creditos-correcao/planejamento.md)) | (a) Usar o id do cartão, se ele for único por aluno e aplicação; (b) criar uma entidade de submissão própria para a correção; (c) usar chave composta de aplicação e aluno | (b) ou (c). A conferência no código mostra que (a) não serve hoje: `cartoes` não tem unicidade por `prova_id` e `aluno_id`, as tentativas são preservadas (`cartao_tentativas`) e não existe entidade de aplicação — `provas.data_aplicacao` é um campo da prova. A entidade de aplicação depende da [FN-059](../059-agendamento-aplicacao-provas/planejamento.md). **Nunca usar o id da prova** | **Pendente** |
| **Pendência jurídica 2: questões enviadas ao acervo** | Separar quatro pontos: contexto de autoria; titularidade e licença de uso; autorização de envio ao banco; direito de reutilização, adaptação e distribuição pelo Gabarito360; e a recompensa à pessoa | Licença não exclusiva de uso, adaptação e distribuição para o Gabarito360, com a autoria contextual preservada ([RN-PRV-16](../../arquitetura/08-regras-de-negocio.md) e [FN-038](../038-disciplinas-curriculo/planejamento.md)) e recompensa na carteira pessoal. Submeter à revisão jurídica, em especial para a questão criada em contexto institucional | **Pendente** |
| **Quantidades e formato comercial dos créditos** (D-2, D-3 e D-4) | Ver as opções e a recomendação na [FN-081 §8](../081-creditos-correcao/planejamento.md), que é dona dessas decisões | Decidir junto com os preços dos planos, na mesma sessão. Sem D-3 e D-4 esta FN não fecha preço nem pacote | **Pendente** |
| Autocadastro e SaaS (MP-027, MP-028, MP-029 e RN-GER-09) | Manter planejados ou declarar substituídos | Declarar o MP-028 e o checkout substituídos e o MP-029 obsoleto. A contratação assistida já está registrada na [RN-GER-09](../../arquitetura/08-regras-de-negocio.md). Os preços entram nesta funcionalidade | **Pendente** |
| Condições de lançamento comercial e regras financeiras de adesão da escola autônoma | — | Tratar neste planejamento, com as condições de [09 §2.3](../../arquitetura/09-interface-e-design-system.md) | **Pendente** |
| ⚠ Legado ativo além do `MercadoPagoService` | O plano mestre diz "sem código, só o `MercadoPagoService` legado". Estão ativos: tabelas `planos`, `assinaturas` e `pagamentos`; `POST /webhooks/mercadopago` (público, HMAC); `GET /assinaturas/minha`; `POST /assinaturas/cancelar` (chama o gateway); web `/assinatura` e `/checkout/*` | Decidir na F1 e na F3: desativar o webhook e o cancelamento até a F3, ou mantê-los. Registrar no plano mestre | **Pendente** (divergência entre plano mestre e código) |
| Rotas `assinaturas/*` sem middleware de permissão | Só autenticação; elas filtram por `titular_id` | Rever na F1, junto da troca de titular por cliente | **Pendente** |
| Regras ainda não especificadas da escola autônoma | Adesão da instância superior, alcance e vigência da assunção, período já pago e encerramento | Não inventar crédito, prorrata nem vencimento. Levar ao responsável | **Pendente** |
| Bloqueio por inadimplência | A regra antiga (RN-015.4, hoje [RN-GER-09](../../arquitetura/08-regras-de-negocio.md), Substituída) definia bloqueio após 7 dias. O pacote F2 diz "só depois de regra definida" | Nenhum bloqueio automático sem regra aprovada. O estado `past_due` não implica bloqueio total imediato (ADR-0061) | **Pendente** |

| Risco | Efeito | Mitigação |
|---|---|---|
| Esquema `billing_` duplicado com o legado | Duas verdades comerciais e conciliação manual | A tabela de cada conceito existe uma só vez (§6.3); a revisão do ADR-0061 confere |
| Migração de `decimal` para centavos | Perda ou arredondamento de valor | Prévia, comparação antes e depois, e reversão ensaiada |
| Esquema definitivo alterado depois de publicar | Migração e reconciliação no Core | Fechar o esquema na F1, contra o ADR-0061 §5 |
| Telas do Financeiro sem mockup | Retrabalho e tela fora do padrão | Subfase 2.0 antes da F2; revisão de UI/UX de outro agente |

## 9. Ramos desta entrega

Nenhum aberto.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
| 2026-11-11 | Subfase 0.2 de adequação: cabeçalho no modelo atual; citações aos documentos antigos trocadas pelos destinos do mapa; RN-015 passou a RN-GER-09 (Substituída); interface em todas as faixas do ADR-0057; conciliação do legado com o esquema `billing_` do [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md); fronteira com a [FN-081](../081-creditos-correcao/planejamento.md) declarada; pendências novas no §8 | lucasgenial |
