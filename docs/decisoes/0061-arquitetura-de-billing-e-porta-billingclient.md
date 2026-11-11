---
id: ADR-0061
titulo: Arquitetura de billing com o M3 Core como fonte da verdade futura e a porta BillingClient no Gabarito360
estado: Aceita
substituida_por: []
data: 2026-11-11
afeta_funcionalidades: [FN-068, FN-081]
afeta_caminhos: ["apps/api/**"]
verificacao: revisao-ia
regras: []
agents_secao: Planejamento
agents_ordem: 70
agents_resumo:
  - "O M3 Core é a futura fonte da verdade do billing do ecossistema (catálogo, assinaturas, franquias, créditos, reservas, consumo e auditoria). Ele é futuro e não bloqueia o Gabarito360. O Core é orientado a catálogo: nenhum código é específico por produto."
  - "O Gabarito360 fala com o billing só pela porta `BillingClient` (`authorize`, `reserve`, `commit`, `release`, `getBalance`). Hoje vale o `LocalBillingAdapter`, no banco do Gabarito360; depois, o `M3CoreBillingAdapter` por HTTP. O resto do Gabarito360 não muda na troca. Billing não é Payments: gateways ficam atrás de um adaptador e só emitem eventos."
  - "As tabelas de billing usam o prefixo `billing_`, com esquema definitivo compatível com o Core. Os registros financeiros e de consumo são append-only: sem UPDATE nem DELETE pela credencial da aplicação. Nenhum preço, franquia ou regra comercial crítica fica fixo no código."
  - "Cada operação tarifada tem um único contexto de cobrança (professor, escola ou núcleo), sem recurso automático a outro contexto; o núcleo delega franquia à escola por alocação explícita. Sem franquia e sem crédito, o envio ao servidor é recusado. Na indisponibilidade do Core, o backend autoriza só dentro de capacidade destacada antes (lease); nada de billing fica no aparelho e a carteira nunca fica negativa."
---
# ADR-0061 — O billing do ecossistema tem o M3 Core como fonte da verdade futura, e o Gabarito360 fala com ele por uma porta

**Data:** 2026-11-11
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** sessão de decisão do responsável em 11/11/2026. Registro histórico: [Decisões de billing, créditos e M3 Core (11/11/2026)](../historico/2026-11-11-decisoes-billing.md)
**Afeta:** [FN-068](../funcionalidades/068-financeiro/planejamento.md), [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md); [03 Modelo de dados](../arquitetura/03-modelo-de-dados.md)

## Contexto

O modo servidor da correção consome processamento pago ([ADR-0058](0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md)). O Gabarito360 precisa cobrar esse consumo antes de publicar o modo servidor. O responsável também planeja o M3 Core, a plataforma comercial do ecossistema (Gabarito360, ECOAR, Vizor.ai e outros). O Core ainda não existe e não pode atrasar o Gabarito360. Uma implementação local feita sem cuidado criaria um segundo sistema comercial, incompatível com o Core. Ela também obrigaria a reinterpretar o histórico na migração. Esta decisão define onde mora a verdade do billing hoje, onde ela vai morar depois, e qual é a fronteira entre os dois.

## Decisão

### 1. O M3 Core é a fonte da verdade futura

O M3 Core é a futura plataforma comercial do ecossistema. Ele é a fonte da verdade sobre catálogo, assinaturas, franquias, créditos, reservas, consumo e auditoria.

O Core é futuro e não bloqueia o Gabarito360.

O Core é orientado a catálogo. Nenhum código é específico por produto: não existe um `Gabarito360BillingService`. Um produto novo entra como registros novos no catálogo.

A interface administrativa do M3 Core segue o loop do projeto: mockup antes de código. Ela fica fora desta decisão.

### 2. Billing não é Payments

Billing e meios de pagamento são camadas separadas. Os gateways ficam atrás de um adaptador e só emitem eventos: `payment_confirmed`, `payment_failed`, `refund_confirmed` e `chargeback`.

Gateways e NFSe ficam fora do MVP.

### 3. A porta `BillingClient`

O Gabarito360 fala com o billing somente pela porta `BillingClient`. As operações mínimas são `authorize`, `reserve`, `commit`, `release` e `getBalance`.

| Fase | Implementação | Onde o dado mora |
|---|---|---|
| Agora | `LocalBillingAdapter` | Banco do Gabarito360, em tabelas com o prefixo `billing_` |
| Futuro | `M3CoreBillingAdapter` | M3 Core, por HTTP |

O esquema local das tabelas `billing_` é **definitivo** e compatível com o Core. Ele não é um protótipo.

Na troca do adaptador, o resto do Gabarito360 não muda. A migração futura é copiar, validar e reconciliar identificadores. Ela não reinterpreta o histórico.

A regra de recusa vale já na fase local: sem franquia e sem crédito suficientes, o envio ao servidor é recusado.

### 4. Contexto de cobrança

A terminologia do produto é Núcleo, Escola e Professor. O Core pode usar abstrações internas (conta, tenant), mas a integração preserva os termos do produto.

Cada operação tem um único contexto de cobrança (`billing_context`): professor, escola ou núcleo. **Não existe recurso automático de um contexto a outro.** Crédito pessoal não financia operação institucional.

A escola não consome recursos do núcleo de forma automática. O núcleo faz uma alocação explícita de franquia para a escola. Isso é delegação de franquia, não transferência de créditos.

Esgotado o contexto, o sistema bloqueia só o consumo tarifado daquele contexto. Ele oferece ao responsável três caminhos: comprar créditos, pedir alocação ao núcleo ou alterar o plano.

### 5. Carteira, franquia e registros append-only

Crédito é a unidade comercial **comum** de consumo de recurso tarifado do ecossistema. Ele não é "custo de IA". O preço de cada recurso em créditos fica no catálogo.

Franquia (allowance) é por recurso e por ciclo, vinculada ao plano. **Franquia não é carteira.**

| Assunto | Regra |
|---|---|
| Origem dos lotes da carteira | `PROMOTIONAL`, `REWARD` e `PURCHASED` |
| Validade | Comprado sem expiração por padrão (`expires_at = null`); promocional e recompensa com validade por política; franquia vence no fim do ciclo |
| Ordem de consumo | 1) franquia do recurso; 2) promocional; 3) recompensa; 4) comprado. Dentro da categoria, usa primeiro o lote que vence antes |
| Tipos de transação | `RESERVE`, `RELEASE`, `CONSUME`, `GRANT`, `REVERSAL`, `REFUND`, `EXPIRE` e `ADJUSTMENT`. Eles não são origens |
| Natureza dos créditos | Não são sacáveis, conversíveis em dinheiro, transferíveis nem negociáveis |

Os registros financeiros e de consumo são **append-only**, no padrão que o projeto já usa ([03 Modelo de dados](../arquitetura/03-modelo-de-dados.md), item 6, e FN-025). A credencial da aplicação não faz `UPDATE` nem `DELETE` neles. Uma correção entra como evento compensatório. A operação administrativa usa credencial e trilha separadas. A regra vale para carteira, franquias, consumo, reservas, reversões, ajustes e reconciliação.

A separação dos registros é esta:

- `billing_usage_events` guarda exatamente um evento lógico de consumo por operação, qualquer que seja a fonte do pagamento.
- `billing_ledger_entries` guarda somente movimentações da carteira.
- As franquias têm registros de consumo próprios, referenciados pelo evento de consumo.
- Pago por franquia: há evento de consumo e nenhuma entrada na carteira. Pago por crédito: há evento de consumo e entrada append-only na carteira.
- O saldo é derivado do ledger, ou materializado a partir dele.
- `REVERSAL` devolve à origem consumida, com a validade original. Se a origem já venceu, o sistema gera uma compensação `PROMOTIONAL`, com `reason = reversal_after_expiration`, no valor do preço registrado na operação original.
- Toda operação sensível registra quem, quando, produto, contexto, recurso, quantidade, origem, motivo e referência. A concessão manual exige quem concedeu, por quê e quando.

Esquema local mínimo (nomes equivalentes são aceitos): `products`, `resources`, `plans`, `plan_entitlements/allowances`, `prices`, `subscriptions`, `billing_operations`, `billing_reservations`, `billing_usage_events`, `billing_allowances`, `billing_wallets`, `billing_wallet_lots`, `billing_ledger_entries` e `billing_contingency_leases`.

Nenhum preço, franquia ou regra comercial crítica fica fixo no código.

### 6. Contingência quando o Core está indisponível

O Core é a fonte da verdade. Na indisponibilidade dele, o **backend** do Gabarito360 autoriza de forma provisória, e só dentro da capacidade que o Core destacou antes: `ALLOWANCE_LEASE` ou `CREDIT_LEASE`. **Nada de billing fica no aparelho.**

Exemplo: saldo 73 e lease de 10. O Core mantém 63 disponíveis e 10 delegados. O consumo em contingência gera evento de consumo local e um registro de saída (outbox). Na reconexão, o consumido vira `COMMIT` e o restante vira `RELEASE`.

A interface mostra o saldo comercial total (73), não 63.

Com o Core disponível, o sistema recolhe o lease não consumido antes de negar saldo. Enquanto houver saldo comercial efetivo, a contingência não causa recusa.

Uma divergência excepcional (erro de programa, corrupção, concessão conflitante ou reconciliação duplicada) vira `RECONCILIATION_EXCEPTION` e é resolvida pela via administrativa. A carteira do usuário nunca fica negativa por falha técnica.

### 7. O que fica no Gabarito360 depois do Core

O perfil interno Financeiro e o cadastro comercial de clientes continuam como interface ([FN-013](../funcionalidades/013-acessos-internos/planejamento.md) e [FN-023](../funcionalidades/023-cadastro-clientes/planejamento.md); regras em [04 Identidade, acesso e permissões](../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1).

Como fonte de dados comerciais, eles são transitórios. Com o Core em operação, o Gabarito360 guarda apenas o `m3_core_account_id`.

## Consequências

- Todo consumo tarifado passa pela porta `BillingClient`. É proibido ler ou escrever tabelas `billing_` fora do adaptador.
- É proibido criar serviço de billing com nome ou regra de um produto específico.
- É proibido guardar saldo, franquia, lease ou reserva no aparelho do usuário.
- É proibido `UPDATE` ou `DELETE` em registro financeiro ou de consumo pela credencial da aplicação.
- É proibido fixar preço, franquia ou regra comercial crítica no código.
- O esquema `billing_` nasce definitivo. Uma mudança nele depois da primeira publicação exige migração e reconciliação.
- As regras operacionais dos créditos de correção não ficam aqui. Elas são da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md).
- Planos, assinaturas, pagamentos, contratos e faturas, e a conciliação deles com o esquema `billing_`, são da [FN-068](../funcionalidades/068-financeiro/planejamento.md).
- A identidade da cobrança da correção passou a ser `correction_subject_id` (aluno × aplicação). O [ADR-0058](0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), item 8, foi corrigido.
- ⚠ Qual componente é a fonte da verdade do esquema `billing_` durante a transição, e depois do Core, é uma pendência do responsável. Ela está na [FN-068 §8](../funcionalidades/068-financeiro/planejamento.md).
- Esta decisão não substitui ADR anterior.

## Verificação

Revisão de IA (`revisao-ia`): em cada PR da FN-068 e da FN-081, o agente de conformidade confere quatro pontos. O código usa só a porta `BillingClient`. Nenhuma regra comercial está fixa no código. Nenhum dado de billing vai ao aparelho. Nenhuma rotina altera ou apaga registro financeiro.

O percurso de aceite do MVP de billing prova a arquitetura de ponta a ponta: cadastrar produto, recurso e plano; atribuir entitlement; criar conta e assinatura; gerar franquia; adicionar créditos; consultar saldo; reservar; executar; confirmar; cancelar ou expirar a reserva; estornar; conceder bônus e recompensa; consultar o ledger; auditar; operar em contingência; e reconciliar. O caso de validação é "corrigir a prova de um aluno usando 1 crédito".

Os testes obrigatórios do consumo da correção estão na [FN-081 §6](../funcionalidades/081-creditos-correcao/planejamento.md).
