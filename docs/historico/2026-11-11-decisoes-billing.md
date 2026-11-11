# Decisões de billing, créditos e M3 Core (11/11/2026) — histórico

> **Documento histórico.** Este é o registro da sessão de decisão do responsável em 11/11/2026. Ele guarda o texto original, para consulta. **Não atualize este arquivo e não o cite como norma.**
>
> Este documento **não é fonte normativa**. Cada assunto tem um documento dono ([ADR-0055](../decisoes/0055-estrutura-da-documentacao-e-documento-dono.md)):
>
> | Assunto | Documento dono |
> |---|---|
> | Arquitetura de billing (M3 Core, porta `BillingClient`, adaptadores, tabelas `billing_`, contingência) | [ADR-0061](../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) |
> | Regras funcionais dos créditos de correção (unidade, reserva, COMMIT, RELEASE, reenvio, recompensas) | [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md) |
> | Planos, assinaturas, pagamentos, contratos, faturas e a conciliação deles com o billing | [FN-068](../funcionalidades/068-financeiro/planejamento.md) |
> | Identidade da cobrança da correção (`correction_subject_id`) | [ADR-0058](../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), item 8 |
>
> Em divergência entre este registro e um documento dono, vale o documento dono. As pendências marcadas **PENDENTE** continuam pendentes; elas estão registradas na [FN-068 §8](../funcionalidades/068-financeiro/planejamento.md#8-riscos-e-decisões-pendentes-do-responsável) e na [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md#8-riscos-e-decisões-pendentes-do-responsável).

Tudo o que segue é decisão do responsável, exceto o que está marcado PENDENTE. O texto está como foi escrito em 11/11/2026.

## 1. Arquitetura
- Gabarito360 é o produto. O M3 Core é a futura plataforma comercial do ecossistema (Gabarito360, ECOAR, Vizor.ai...) e fonte da verdade sobre catálogo, assinaturas, franquias, créditos, reservas, consumo e auditoria.
- O M3 Core é futuro e não bloqueia o Gabarito360.
- O Core é orientado a catálogo: nenhum código específico por produto (não existe `Gabarito360BillingService`). Produto novo = registros novos no catálogo.
- Billing ≠ Payments. Gateways ficam atrás de um adaptador e emitem eventos (`payment_confirmed`, `payment_failed`, `refund_confirmed`, `chargeback`). Gateways e NFSe ficam fora do MVP.
- O Gabarito360 usa uma porta `BillingClient` (mínimo: `authorize`, `reserve`, `commit`, `release`, `getBalance`).
  - Agora: `LocalBillingAdapter`, no banco do Gabarito360, tabelas com prefixo `billing_` e **esquema definitivo** compatível com o Core.
  - Futuro: `M3CoreBillingAdapter` via HTTP. O restante do Gabarito360 não muda.
  - Migração futura = copiar, validar e reconciliar ids, sem reinterpretar histórico.
- Já na fase local: sem franquia e sem crédito suficientes, o envio ao servidor é recusado.
- O perfil interno Financeiro e o cadastro comercial de clientes existentes (FN-013 e FN-023; regras em [04 Identidade, acesso e permissões](../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1) permanecem como interface. São transitórios como fonte de dados; com o Core, o Gabarito360 guarda só `m3_core_account_id`.
- Interface administrativa do M3 Core segue o loop do projeto (mockup antes de código) e está fora deste documento.

## 2. Crédito e franquia
- Crédito = unidade comercial **comum** de consumo de recurso tarifado do ecossistema (não é "custo de IA"). O preço de cada recurso em créditos fica no catálogo.
- "1 crédito de correção" significa: a correção de um aluno custa 1 crédito do saldo comum.
- Franquia (allowance) é por recurso e por ciclo, vinculada ao plano. **Não é carteira.**
- Lotes da carteira têm origem: `PROMOTIONAL`, `REWARD`, `PURCHASED`.
- Validade: comprado sem expiração por padrão (`expires_at = null`); promocional e recompensa com validade por política; franquia vence no fim do ciclo.
- Ordem de consumo: 1) franquia do recurso; 2) promocional; 3) recompensa; 4) comprado. Dentro da categoria, o lote que vence primeiro é usado primeiro.
- Créditos não são sacáveis, conversíveis em dinheiro, transferíveis nem negociáveis.
- Tipos de transação (não são origens): `RESERVE`, `RELEASE`, `CONSUME`, `GRANT`, `REVERSAL`, `REFUND`, `EXPIRE`, `ADJUSTMENT`.
- Estados de assinatura: `trialing`, `active`, `past_due`, `suspended`, `canceled`, `expired`. `past_due` não implica bloqueio imediato total.
- Falta de saldo bloqueia só o recurso tarifado; o restante do sistema continua disponível conforme o plano.

## 3. Contexto de cobrança
- Terminologia do produto: Núcleo → Escola → Professor. O Core pode usar abstrações internas (account/tenant), mas a integração preserva os termos do produto.
- Cada operação tem um único `billing_context` (professor, escola ou núcleo). **Sem fallback automático entre contextos.**
- Créditos pessoais não financiam operações institucionais. Esgotado o contexto, bloqueia-se o consumo tarifado dele e oferece-se ao responsável: comprar créditos, pedir alocação ao Núcleo ou alterar plano.
- A escola não consome recursos do Núcleo automaticamente. O Núcleo faz `allocation` explícita de franquia para a escola (delegação de franquia, não transferência de créditos).

## 4. Correção no servidor
- Recurso `correction.server.student` = 1 crédito. Unidade: 1 submissão de 1 aluno em 1 aplicação de prova, independentemente do número de páginas.
- `operation_key = correction:{correction_subject_id}`, onde `correction_subject_id` = identificador único da submissão/cartão de um aluno em uma aplicação.
  - **PENDENTE TÉCNICO:** confirmar qual entidade/tabela já representa de forma única aluno + aplicação. Se o cartão já tiver essa unicidade, seu id será usado diretamente. Nunca usar o id da prova.
- `billing_operations` com `UNIQUE(product, resource, operation_key)`. Uma operação pode ter várias reservas (`billing_reservations`); **no máximo um COMMIT**.
- A reserva existe só em envio → fila → processamento, renovada por heartbeat do worker. TTL é configuração operacional.
- **COMMIT** quando a correção chega a `READY_FOR_REVIEW`. Critérios: aplicação identificada; aluno/cartão identificado; conjunto esperado de páginas recebido; páginas tecnicamente processáveis; estrutura da avaliação reconhecida; resultado estruturado persistido.
- Pendências de respostas individuais (não reconhecida, dupla, ambígua) não impedem a cobrança.
- Não cobra (sem COMMIT; `RELEASE`) quando há: página faltando, aplicação ou cartão não identificáveis, imagem inutilizável, arquivo corrompido, processamento interrompido, estrutura não reconhecida → `NEEDS_RECAPTURE` ou `PROCESSING_FAILED`.
- O professor pode marcar uma página esperada como `NOT_DELIVERED` (confirmação humana). A submissão passa a contar como completa; atendidos os demais critérios, chega a `READY_FOR_REVIEW` e consome normalmente. `NOT_DELIVERED` ≠ página ausente.
- A conferência humana (`READY_FOR_REVIEW` → `FINALIZED`) não interfere na cobrança.
- A identidade da operação é permanente. Reenvio/nova foto e recálculo por alteração de gabarito **não cobram**. Hash de imagem não é identidade de cobrança.
- Nova cobrança só em nova aplicação/tentativa real (recuperação, segunda chamada, nova aplicação), que gera novo `correction_subject_id`.

## 5. Registros e auditoria
- `billing_usage_events`: exatamente um evento lógico de consumo por operação, qualquer que seja a fonte. Campos mínimos: `operation_id`, `resource_id`, `quantity`, `funding_source` (`ALLOWANCE` | `WALLET`), `allowance_id?`, `wallet_ledger_entry_id?`, `catalog_price_snapshot`.
- `billing_ledger_entries`: somente movimentações da carteira.
- Franquias têm registros/eventos de consumo próprios, referenciados pelo usage_event.
- Pago por franquia: há usage_event e nenhuma entrada na carteira. Pago por crédito: usage_event + entrada append-only da carteira.
- Saldo é derivado do ledger (ou materializado a partir dele).
- Reutilizar o padrão append-only já usado no projeto ([03 Modelo de dados](../arquitetura/03-modelo-de-dados.md), FN-025): registros financeiros e de consumo são append-only para a credencial da aplicação; sem UPDATE/DELETE; correções por eventos compensatórios; operações administrativas com credenciais e trilha separadas. Vale para carteira, franquias, consumo, reservas, reversões, ajustes e reconciliação.
- `REVERSAL` devolve à origem consumida, com a validade original. Se a origem já venceu (ex.: franquia de ciclo encerrado): compensação `PROMOTIONAL` com `reason = reversal_after_expiration`, no valor do `catalog_price_snapshot` da operação original.
- Toda operação sensível registra quem, quando, produto, contexto, recurso, quantidade, origem, motivo e referência. Concessão manual exige quem concedeu, por quê e quando.
- Esquema local mínimo (nomes equivalentes aceitos): `products`, `resources`, `plans`, `plan_entitlements/allowances`, `prices`, `subscriptions`, `billing_operations`, `billing_reservations`, `billing_usage_events`, `billing_allowances`, `billing_wallets`, `billing_wallet_lots`, `billing_ledger_entries`, `billing_contingency_leases`. Nenhum preço, franquia ou regra comercial crítica fica fixo no código.

## 6. Contingência (Core indisponível)
- O Core é a fonte da verdade. Em contingência, o **backend** do Gabarito360 autoriza provisoriamente só dentro de capacidade previamente destacada pelo Core: `ALLOWANCE_LEASE` ou `CREDIT_LEASE`. Nada disso fica no celular.
- Ex.: saldo 73, lease 10 → 63 disponíveis no Core, 10 delegados. Consumo offline gera usage_event local + outbox. Na reconexão: consumidos → COMMIT; restante → RELEASE.
- A interface mostra o saldo comercial total (73), não 63.
- Com o Core disponível, o lease não consumido é recolhido automaticamente antes de negar saldo. Enquanto houver saldo comercial efetivo, não há recusa causada pela contingência.
- Divergência excepcional (bug, corrupção, concessão conflitante, reconciliação duplicada) → `RECONCILIATION_EXCEPTION`, resolvida administrativamente. A carteira do usuário nunca fica negativa por contingência técnica.

## 7. Créditos por contribuição validada
- Nome oficial: "Créditos por contribuição validada". Não é gamificação nem a mecânica vedada pelo ECA Digital em [11 Segurança e privacidade](../arquitetura/11-seguranca-e-privacidade.md) (item 5 dos pontos jurídicos), que trata das telas de alunos. O aluno não participa.
- Crédito só após validação/curadoria aprovada (enviar não gera crédito).
- Fluxo: professor contribui → contribuição validada → regra calcula → concessão (`GRANT`, origem `REWARD`) registrada na carteira. O GII não tem autoridade sobre créditos.
- Vai para a carteira **pessoal** de quem contribuiu, em qualquer contexto em que contribuiu. É intencional que professores só institucionais acumulem saldo pessoal. Não pode ser transferido para a escola.

## 8. Critério de aceite do MVP
Percorrer: cadastrar produto, recurso e plano; atribuir entitlement; criar conta e assinatura; gerar franquia; adicionar créditos; consultar saldo; reservar; executar; confirmar; cancelar/expirar reserva; estornar; conceder bônus e recompensa; consultar ledger; auditar; operar em contingência; reconciliar. Validar com "corrigir a prova de um aluno usando 1 crédito".
Testes obrigatórios:
- A. Reenvio de foto → mesmo operation_key → nenhuma cobrança adicional.
- B. Contingência → consumo via lease → reconciliação → sem duplicidade, saldo consistente.
- C. Alterar gabarito após correção → recálculo → nenhum crédito adicional.
- D. Mesma mensagem da fila entregue duas vezes → um único COMMIT.
- E. Worker morre após RESERVE e antes do resultado → reserva expira/libera → nenhum crédito perdido.

## 9. Pendências
- **PENDENTE TÉCNICO:** entidade única aluno + aplicação (seção 4).
- **PENDENTE JURÍDICO 1:** compra de créditos, direito de arrependimento (CDC art. 49), reembolso e pacote parcialmente consumido.
- **PENDENTE JURÍDICO 2:** questões enviadas ao GII, separando: contexto de autoria; titularidade/licença de uso; autorização de envio ao banco; direito de reutilização, adaptação e distribuição pelo Gabarito360; recompensa à pessoa. Especialmente quando criadas em contexto institucional (autoria contextual: [08 Regras de negócio](../arquitetura/08-regras-de-negocio.md), RN-PRV-16, e FN-038).

## 10. Encaixe na documentação atual (verificado em 11/11/2026)
- Dono do assunto: FN-081 (Créditos de correção), hoje na branch `docs/leitor360` (PR #272). Estas decisões respondem às D-1 a D-9 dela.
- Mudam texto já marcado como decidido:
  - ADR-0058 item 8 e FN-081 §6.1 dizem "aluno × prova". Passa a ser `correction_subject_id` (aluno × aplicação).
  - FN-081 §6.4 diz "um reenvio de aluno sem essa marca cobra". Substituída: qualquer reenvio ou nova foto do mesmo `correction_subject_id` (mesmo aluno na mesma aplicação) não cobra, com ou sem marca de dificuldade. A marca pode manter função operacional, mas não decide a cobrança.
  - FN-081 D-8 (recompensas): mesma moeda, origem `REWARD`.
- Ficam abertos: FN-081 D-2 (quem da escola pode consumir o saldo da escola), D-3 (quantidades por plano) e D-4 (formato da compra avulsa).
- FN-068 (Financeiro) já tem `planos`, `assinaturas` e `pagamentos` legados (MP-027) e a regra "sem devolução de valores já pagos" (docs/163). O esquema `billing_` precisa ser conciliado com ela, sem duplicar tabelas. A regra "sem devolução" não vale como regra do novo billing: ela conflita com a pendência jurídica 1 e fica registrada como conflito até a revisão jurídica.

## Fora do escopo
Implementação do M3 Core, gateways, NFSe, infraestrutura (VMs, subdomínios) e mockups do M3 Core.
