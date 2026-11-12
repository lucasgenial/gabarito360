---
id: ADR-0061
titulo: O financeiro é um aplicativo externo independente, e o Gabarito360 fala com ele pela porta BillingClient
estado: Substituída
substituida_por: [ADR-0062]
data: 2026-11-11
afeta_funcionalidades: [FN-068, FN-081]
afeta_caminhos: ["apps/api/**", "apps/web/**"]
verificacao: revisao-ia
regras: []
---
# ADR-0061 — O financeiro é um aplicativo externo independente, e o Gabarito360 fala com ele pela porta `BillingClient`

**Atualizado em:** 12/11/2026, por lucasgenial (revisão: financeiro externo; `LocalBillingAdapter` superado; substituição parcial pelo ADR-0062: Plano e uso e Central do cliente)
**Data:** 2026-11-11
**Estado:** Substituída por [ADR-0062](0062-plano-e-uso-e-central-do-cliente.md) (parcial: os §1, §3, §4, §6 e §7 e as Consequências continuam vigentes e estão reafirmados no ADR-0062; mudaram a finalidade do §2 e o lugar e a forma da governança institucional do §5)
**Decisor:** responsável (lucasgenial)
**Origem:** sessão de decisão do responsável em 11/11/2026 ([registro histórico](../historico/2026-11-11-decisoes-billing.md)); diretriz do responsável de 12/11/2026, que retoma a intenção de 06/11/2026 de ter o billing em outro aplicativo
**Afeta:** [FN-068](../funcionalidades/068-financeiro/planejamento.md), [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md); [14 Integração com o aplicativo financeiro](../arquitetura/14-integracao-financeira.md); [01 Contexto e containers](../arquitetura/01-contexto-e-containers.md)

## Contexto

O modo servidor da correção consome processamento pago ([ADR-0058](0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md)). O Gabarito360 precisa de controle de consumo antes de publicar esse modo.

A primeira versão desta decisão (11/11/2026) punha o billing no banco do Gabarito360, pelo `LocalBillingAdapter`, e previa migrar depois para o M3 Core. Essa versão criava carteira, ledger e esquema financeiro locais.

O responsável já planejava, desde 06/11/2026, um aplicativo separado para o billing, com API e infraestrutura próprias. Em 12/11/2026, ele restabeleceu essa direção. Ele quer preservar a finalidade educacional e o desempenho do Gabarito360.

## Decisão

As marcas usadas abaixo são as do [contrato](../arquitetura/14-integracao-financeira.md) §0: **[Diretriz]**, **[Histórico]**, **[Proposto]** e **[Pendente]**.

### 1. O financeiro é um aplicativo externo independente **[Diretriz]**

O aplicativo financeiro tem código-fonte, banco, serviço, credenciais e implantação separados do Gabarito360. O Gabarito360 o acessa só por integração. Ele não acessa o banco financeiro.

A direção funcional reservada a esse aplicativo é: catálogo comercial, planos, assinaturas, pagamentos, créditos, bônus, franquias, recompensas, autorização de consumo, reservas, contabilização, reversões, regras de distribuição institucional e auditoria financeira.

Esta decisão define a fronteira. Ela não define a arquitetura interna do aplicativo financeiro. Ela não cria agora o repositório, as tabelas, a stack nem os meios de pagamento dele.

O nome de trabalho encontrado na documentação é M3 Core. A marca definitiva não está escolhida. O aplicativo é futuro e não bloqueia o Gabarito360.

### 2. O módulo financeiro do Gabarito360 é limitado **[Diretriz]**

O módulo dá acesso contextual às capacidades financeiras que o produto precisa. Ele não é um sistema financeiro completo. Ele não administra o aplicativo externo.

O Gabarito360 tem:

- a porta `BillingClient` e o cliente da API do aplicativo financeiro;
- a identidade da correção (`correction_subject_id`) e o registro operacional dela;
- referências externas opacas (conta e operação no aplicativo financeiro);
- a exibição de saldo, limites e histórico recebidos do aplicativo financeiro.

O Gabarito360 não tem carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo nem esquema financeiro local.

### 3. O contrato tem um dono

O contrato de integração está em [14 Integração com o aplicativo financeiro](../arquitetura/14-integracao-financeira.md). Ele define capacidades, contextos, dados mínimos, consumo, idempotência, respostas, notificações e falhas. Ele não inventa endpoints, tecnologias nem números.

### 4. Fronteira de confiança **[Diretriz]**

- O aplicativo financeiro autentica o Gabarito360 e autoriza cada ação no contexto permitido.
- A credencial técnica do Gabarito360 não administra o financeiro.
- Ações humanas financeiras exigem identidade, vínculo e permissão próprios, validados pelo aplicativo financeiro.
- Os produtos não compartilham banco, usuário administrativo de banco nem segredos de gateway.

Os controles técnicos que concretizam estes pontos são **[Proposto]** e estão no contrato, §8.

### 5. Contexto de cobrança **[Histórico]**

- Os termos do produto são Núcleo, Escola e Professor.
- Cada operação tem um único contexto de cobrança. Não existe recurso automático de um contexto a outro.
- As carteiras pessoal e institucional ficam isoladas. A hierarquia não autoriza consumo implícito do saldo de outro contexto.
- O núcleo delega franquia à escola só por alocação explícita.

**[Diretriz]** O responsável financeiro institucional pode comprar créditos para a entidade e administrar limites mensais de consumo por professor. Isso é governança do consumo institucional. Não é transferência para a carteira pessoal.

### 6. Disponibilidade e testes **[Diretriz]**

- Sem autorização válida, o envio ao modo servidor é recusado.
- A indisponibilidade da API financeira nunca ativa o simulador nem um modo "autoriza tudo".
- Não existe autorização sem serviço disponível ou sem concessão válida.
- A ausência do aplicativo financeiro não bloqueia os testes do Gabarito360. A primeira entrega da integração é um simulador genérico da porta `BillingClient` ([contrato](../arquitetura/14-integracao-financeira.md) §9).
- O processamento educacional é real: login, permissões, aplicação, submissão, reenvio, fila, processamento, `READY_FOR_REVIEW` e conferência. Só a autorização, o saldo, a reserva e a confirmação vêm do simulador.
- O estado do simulador é fictício, separado das tabelas do produto, reiniciável e identificado como simulação. Ele não tem valor comercial, nunca é migrado e não é carteira, ledger nem motor financeiro.
- O simulador só funciona com ativação explícita no servidor, em desenvolvimento e homologação. Ele não gera pagamento, dívida nem cobrança retroativa, e não libera permissões educacionais.
- **[Pendente]** Piloto em produção com simulação. Ele não está habilitado.
- A tela pode exibir, de forma temporária, dados financeiros autorizados para o usuário (saldo, limites e histórico do contexto).
- O aparelho não persiste esses dados. Ele não guarda saldo autoritativo, carteira nem concessão de contingência, e não decide autorização financeira.
- O cliente nunca recebe credenciais técnicas nem administrativas.

### 7. Legado **[Diretriz]**

Esta decisão é só de documentação. Ela não remove nem desliga o legado do MP-027 (checkout, assinaturas, webhook do Mercado Pago, dados e tabelas). A situação do legado é da [FN-068](../funcionalidades/068-financeiro/planejamento.md).

## Revisão de 12/11/2026: o que foi superado

A versão de 11/11/2026 desta decisão continua no [registro histórico](../historico/2026-11-11-decisoes-billing.md) e no commit `ca6109d5` do PR #274. A tabela abaixo diz o que dela foi superado e o que continua.

| Item da versão de 11/11/2026 | Situação |
|---|---|
| `LocalBillingAdapter` no banco do Gabarito360, como fase "agora" | **Superado.** Não serve nem como etapa temporária para migrar saldos depois. |
| Esquema `billing_` "definitivo" no Gabarito360 (`products`, `plans`, `subscriptions`, `billing_wallets`, `billing_ledger_entries`, `billing_contingency_leases` e outros) | **Superado.** O Gabarito360 não cria esquema financeiro. |
| Migração futura por cópia e reconciliação de ids entre o Gabarito360 e o Core | **Superado.** Não há base local a migrar. |
| Lease de contingência guardado no backend do Gabarito360, com evento de consumo local e outbox | **Superado como implementação.** A intenção (capacidade concedida antes e reconciliação, sem saldo negativo) continua. O protocolo está pendente (contrato §10). |
| Recomendação de o Gabarito360 ser dono temporário do esquema `billing_` (FN-068 §8) | **Superado.** A recomendação nunca foi aprovada. |
| Porta `BillingClient` e capacidades `authorize`, `reserve`, `commit`, `release` e `getBalance` | **Continua**, como porta do cliente da API. |
| `M3CoreBillingAdapter` por HTTP | **Continua**, com outro nome: cliente da API do aplicativo financeiro. |
| Billing não é Payments; gateways atrás de adaptador | **Continua**, dentro do aplicativo financeiro. |
| Core orientado a catálogo, sem código por produto | **Continua**, como requisito do aplicativo financeiro. |
| Origens dos lotes, validade, ordem de consumo, tipos de transação, append-only, reversão e compensação | **Continuam** como requisitos do aplicativo financeiro. Elas não são implementadas no Gabarito360. |
| Contexto único de cobrança e alocação explícita de franquia | **Continuam** (§5). |
| Recusa sem franquia e sem crédito | **Continua** (§6). |
| Perfil interno Financeiro e cadastro comercial como interface; guardar só a referência da conta | **Continua em parte.** O Gabarito360 guarda referências externas. O papel do perfil Financeiro depois do aplicativo externo está pendente (FN-068 §8). |

## Consequências

- Todo consumo tarifado passa pela porta `BillingClient`, segundo o [contrato](../arquitetura/14-integracao-financeira.md).
- É proibido criar no Gabarito360 carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo ou esquema financeiro local.
- É proibido conectar o Gabarito360 ao banco do aplicativo financeiro.
- É proibido guardar no Gabarito360 credencial administrativa do aplicativo financeiro ou segredo de gateway.
- É proibido ativar o simulador, ou liberar o consumo, porque a API financeira caiu.
- É proibido ativar o simulador em produção sem decisão expressa do responsável.
- É proibido persistir no aparelho (armazenamento do navegador ou do aparelho) os dados financeiros exibidos.
- É proibido manter no cliente saldo autoritativo, carteira, concessão de contingência ou autorização financeira.
- É proibido expor ao cliente credenciais técnicas ou administrativas.
- A API real implementa a mesma porta `BillingClient` que o simulador. Ela é validada contra o [contrato](../arquitetura/14-integracao-financeira.md) antes da troca.
- As regras funcionais da correção são da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md).
- A experiência financeira limitada, a integração e o legado são da [FN-068](../funcionalidades/068-financeiro/planejamento.md).
- A identidade da cobrança da correção é `correction_subject_id` (aluno × aplicação) ([ADR-0058](0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), item 8).
- O aplicativo financeiro e a documentação interna dele não são criados neste repositório. Eles são dependência externa.
- Esta decisão não substitui ADR anterior. A revisão de 12/11/2026 substitui a versão de 11/11/2026 desta mesma decisão, que nunca chegou à `main`.

## Verificação

Revisão de IA (`revisao-ia`): em cada PR da FN-068 e da FN-081, o agente de conformidade confere sete pontos.

1. O código fala com o financeiro só pela porta `BillingClient`.
2. Nenhuma migration do Gabarito360 cria carteira, ledger, lotes, saldo, preço ou tabela financeira nova. O estado do simulador fica separado das tabelas do produto.
3. Nenhuma configuração do Gabarito360 tem conexão ao banco financeiro, credencial administrativa ou segredo de gateway.
4. Nenhum caminho de código ativa o simulador, ou autoriza consumo, quando a API financeira falha. A configuração de produção não ativa o simulador.
5. O cliente só exibe dados financeiros autorizados, de forma temporária. Ele não os persiste, não guarda saldo autoritativo, carteira nem concessão de contingência, não decide autorização e não recebe credenciais técnicas ou administrativas.
6. O legado do MP-027 só muda em tarefa própria, autorizada.
7. A troca do simulador pela API real só ocorre depois de a API real passar na validação contra o contrato.

Os testes obrigatórios do consumo da correção estão na [FN-081 §6.7](../funcionalidades/081-creditos-correcao/planejamento.md).
