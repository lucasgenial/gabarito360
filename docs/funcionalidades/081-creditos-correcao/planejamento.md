# 081 — Créditos de correção — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Conforme princípios:** versão 17
**Posição no plano mestre:** fora das ondas (candidata; próxima documentação, aguarda priorização do responsável)
**Natureza:** nova
**Origem:** surgiu no planejamento do Leitor360 (FN-079) e do Escriba (FN-080) em 2026-11-11, com o modo servidor
**Atualizado em:** 2026-11-11, por lucasgenial

Planejamento preliminar. Ele reúne o que já está decidido e as perguntas que continuam abertas. Nada é executado antes da priorização e da aprovação destes documentos.

Neste documento, "(proposta)" significa: proposta do planejamento, a confirmar pelo responsável. "⚠" marca o que ainda não foi confirmado.

Esta funcionalidade é **dona das regras funcionais dos créditos de correção**. A arquitetura de billing que a sustenta (M3 Core, porta `BillingClient`, adaptadores, tabelas `billing_` append-only, carteira, franquia e contingência) é do [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md). Planos, assinaturas, pagamentos, contratos e faturas são da [FN-068](../068-financeiro/planejamento.md).

## 1. Objetivo

O processamento no aparelho do usuário não custa nada ao Gabarito360. O processamento no servidor custa. Esta funcionalidade controla esse custo com créditos de correção. Ela reserva, confirma e libera o consumo de cada correção feita no servidor, recusa o envio sem saldo e mostra o extrato. Ela também liga o consumo à franquia do plano e à compra avulsa.

## 2. Escopo

- **Inclui:**
  - Consumo do recurso tarifado `correction.server.student`, ao preço de 1 crédito por submissão.
  - Identidade da cobrança por `correction_subject_id` (um aluno em uma aplicação de prova), com reserva, confirmação (`COMMIT`) e liberação (`RELEASE`).
  - Idempotência: o mesmo `correction_subject_id` nunca cobra duas vezes, qualquer que seja o número de páginas, de reenvios ou de mensagens repetidas da fila.
  - Recusa do envio sem franquia e sem crédito suficientes, com mensagem.
  - Aviso prévio de consumo no Leitor360 (o texto já está decidido na FN-079).
  - Saldo, extrato e trilha de auditoria do consumo, no contexto de cobrança da operação.
  - Franquia do plano e compra avulsa acima da franquia (quantidades e formato: D-3 e D-4, abertas).
  - Créditos por contribuição validada (programa de recompensa do professor), na mesma moeda.
  - Alertas de saldo baixo (proposta).
- **Não inclui:**
  - A arquitetura de billing, a porta `BillingClient`, o esquema `billing_` e a contingência → [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md).
  - Contratos, faturas, pagamentos, planos e assinaturas, e a conciliação deles com o esquema `billing_` → [FN-068](../068-financeiro/planejamento.md). Esta FN pede a cobrança da compra avulsa à FN-068.
  - O modo servidor, a fila e o marcador de identificação → [FN-079](../079-leitor360/planejamento.md) §6.12.
  - O Escriba no servidor → [FN-080](../080-ia-local-transcricao/planejamento.md) §8.3.
  - Aviso ao terminar a fila → [FN-057](../057-notificacoes/planejamento.md).
  - A curadoria das questões enviadas ao acervo, que antecede a recompensa → [FN-044](../044-acervo-gii/planejamento.md). O acervo não tem autoridade sobre créditos.
  - O M3 Core, os gateways e a NFSe → fora do escopo do projeto nesta fase (ADR-0061).

## 3. Fontes de verdade

- Arquitetura de billing: [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md).
- Identidade da cobrança e modo servidor: [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), item 8; [FN-079 §6.12 e §8](../079-leitor360/planejamento.md); [FN-080 §8.3](../080-ia-local-transcricao/planejamento.md).
- Registro histórico da sessão de decisão de 11/11/2026: [Decisões de billing, créditos e M3 Core](../../historico/2026-11-11-decisoes-billing.md). Ele não é norma.
- Telas: mockup aprovado `mockups/leitor360.html`, tela H (`data-tela="sem-ia"`) e modal "Usar a correção no servidor". As telas próprias de saldo, extrato e compra vão ao mockup na subfase 0.1 ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md) e [ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)).
- Financeiro: [FN-068](../068-financeiro/planejamento.md).
- Perfis e permissões: [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| ADR-0061 billing | Define a porta, o esquema `billing_` e a contingência que esta FN usa | Aceita |
| FN-068 financeiro | Planos e franquias; a compra avulsa gera cobrança e pagamento; conciliação do legado com o `billing_` | Em planejamento |
| FN-079 leitor360 | Estados da submissão que disparam `RESERVE`, `COMMIT` e `RELEASE` | Planejamento aprovado |
| FN-004 auditoria | Trilha de concessões e consumos | Parcial |
| FN-057 notificacoes | Alerta de saldo baixo (proposta) | Em planejamento |

**Funcionalidades que dependem desta:** FN-079 (subfase 4.3, modo servidor das objetivas) e FN-080 / FN-079 6.3 (Escriba no servidor). Elas não publicam o modo servidor sem esta FN.

## 5. Fases e subfases (preliminares)

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Planejamento, casos de uso e mockups aprovados, com as decisões D-2, D-3 e D-4 respondidas | O responsável aprova os documentos e o mockup das telas de saldo, extrato e compra | Pendente | |
| 1.1 | Consumo pela porta `BillingClient`: operação, reserva, `COMMIT`, `RELEASE` e `REVERSAL` | Os testes A a E do §6 passam; nenhum saldo fica negativo; a concorrência no MariaDB não gera consumo duplo | Pendente | |
| 1.2 | Consumo integrado ao modo servidor (FN-079 4.3) | Sem franquia e sem crédito, o envio é recusado antes de entrar na fila; o `COMMIT` só ocorre em `READY_FOR_REVIEW` | Pendente | |
| 2.1 | Telas de saldo e extrato | Conforme mockup aprovado; responsivo em todas as faixas (ADR-0057) | Pendente | |
| 3.1 | Franquia do plano e compra avulsa (com FN-068) | A franquia renova no ciclo; a compra avulsa só credita depois do pagamento confirmado | Pendente | |
| 4.1 | Créditos por contribuição validada | A concessão só ocorre depois da curadoria aprovada; ela cai na carteira pessoal de quem contribuiu | Pendente | |

## 6. Contrato técnico

Toda leitura e toda escrita de saldo passam pela porta `BillingClient` ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §3). A ordem de consumo, as origens dos lotes, os tipos de transação e a regra append-only são do ADR-0061 §5. Os itens abaixo são as regras próprias da correção.

### 6.1 Unidade e identidade da cobrança

1. **Recurso e preço.** O recurso tarifado é `correction.server.student`. Ele custa 1 crédito. O preço fica no catálogo, nunca no código.
2. **Unidade.** Uma submissão de um aluno em uma aplicação de prova, qualquer que seja o número de páginas.
3. **Identidade.** `operation_key = correction:{correction_subject_id}`. O `correction_subject_id` identifica de forma única o par aluno + aplicação.
   - ⚠ **Pendência técnica:** qual entidade ou tabela já representa esse par de forma única. Se o cartão já tiver essa unicidade, o sistema usa o id dele. **Nunca use o id da prova.** Opções e recomendação na [FN-068 §8](../068-financeiro/planejamento.md).
4. **Unicidade no banco.** `billing_operations` tem `UNIQUE(product, resource, operation_key)`. Uma operação pode ter várias reservas (`billing_reservations`), mas **no máximo um `COMMIT`**.
5. **A identidade é permanente.** Reenvio, nova foto e recálculo por alteração de gabarito não geram operação nova e não cobram. O hash da imagem não é identidade de cobrança.
6. **Cobrança nova só em aplicação ou tentativa real nova** (recuperação, segunda chamada, nova aplicação). Ela gera um `correction_subject_id` novo.
7. **Só no servidor.** O processamento no aparelho nunca consome crédito. A chave `leitura.transcrever` não cobra no aparelho.

### 6.2 Reserva

8. A reserva existe apenas no trecho envio → fila → processamento. O worker a renova por heartbeat.
9. O tempo de vida (TTL) da reserva é configuração operacional, não regra de negócio.
10. Sem franquia e sem crédito suficientes no contexto de cobrança, o envio é recusado antes de entrar na fila.

### 6.3 Quando cobra (`COMMIT`)

11. O `COMMIT` ocorre quando a correção chega a `READY_FOR_REVIEW`. Todos estes critérios são atendidos:
    - a aplicação está identificada;
    - o aluno ou o cartão está identificado;
    - o conjunto esperado de páginas chegou;
    - as páginas são tecnicamente processáveis;
    - a estrutura da avaliação foi reconhecida;
    - o resultado estruturado foi persistido.
12. Pendências de respostas individuais (marcação não reconhecida, dupla ou ambígua) não impedem a cobrança.
13. A conferência humana (`READY_FOR_REVIEW` → `FINALIZED`) não interfere na cobrança.
14. O professor pode marcar uma página esperada como `NOT_DELIVERED`. Essa é uma confirmação humana. A submissão passa a contar como completa. Atendidos os demais critérios, ela chega a `READY_FOR_REVIEW` e consome normalmente. `NOT_DELIVERED` não é o mesmo que página ausente.

### 6.4 Quando não cobra (`RELEASE`)

15. Não há `COMMIT`, e a reserva é liberada, em qualquer destes casos: página faltando; aplicação ou cartão não identificáveis; imagem inutilizável; arquivo corrompido; processamento interrompido; estrutura não reconhecida. A submissão vai para `NEEDS_RECAPTURE` ou `PROCESSING_FAILED`.
16. **Qualquer reenvio ou nova foto do mesmo `correction_subject_id` não cobra**, com ou sem marca de dificuldade do servidor. A marca de dificuldade pode manter função operacional, mas ela não decide a cobrança.

### 6.5 Registros

17. Cada operação gera exatamente um evento lógico de consumo em `billing_usage_events`, qualquer que seja a fonte do pagamento (franquia ou carteira). Campos mínimos, origens, ledger e reversões: [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §5.
18. Os registros são append-only. Nenhum lançamento é apagado ou alterado.
19. **Dados de aluno.** O extrato mostra aluno e aplicação só para quem tem acesso à prova. O perfil Financeiro vê apenas totais (regra da FN-068: o Financeiro não acessa dados de alunos).

### 6.6 Créditos por contribuição validada

20. O nome oficial do programa é "Créditos por contribuição validada". Ele não é gamificação. Ele não é a mecânica vedada pelo ECA Digital em [11 Segurança e privacidade](../../arquitetura/11-seguranca-e-privacidade.md) (item 5 dos pontos jurídicos), que trata das telas de alunos. **O aluno não participa.**
21. O crédito só nasce depois da validação ou da curadoria aprovada. Enviar uma contribuição não gera crédito.
22. Fluxo: o professor contribui; a contribuição é validada; a regra calcula; o sistema registra a concessão (`GRANT`, origem `REWARD`) na carteira.
23. O crédito vai para a carteira **pessoal** de quem contribuiu, em qualquer contexto em que a pessoa contribuiu. É intencional que um professor só institucional acumule saldo pessoal. Esse saldo não pode ser transferido para a escola.
24. ⚠ A titularidade, a licença de uso e a autorização de envio das questões ao acervo dependem da pendência jurídica 2, registrada na [FN-068 §8](../068-financeiro/planejamento.md).

### 6.7 Testes obrigatórios

| # | Teste | Resultado esperado |
|---|---|---|
| A | Reenvio de foto do mesmo `correction_subject_id` | Mesmo `operation_key`; nenhuma cobrança adicional |
| B | Contingência: consumo por lease e depois reconciliação | Sem duplicidade; saldo consistente |
| C | Alterar o gabarito depois da correção e recalcular | Nenhum crédito adicional |
| D | A mesma mensagem da fila entregue duas vezes | Um único `COMMIT` |
| E | O worker morre depois do `RESERVE` e antes do resultado | A reserva expira e libera; nenhum crédito é perdido |

## 7. Interface

O app é responsivo ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)). Cada tela e cada modal desta funcionalidade é conferido nas larguras 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro, com registro por texto ([ADR-0049](../../decisoes/0049-sem-prints-da-aplicacao.md)).

Regra do mockup ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md)): cada tela usa a casca, os componentes e o CSS reais de `apps/web`, e o design system de `style-system/`.

Telas previstas (mockup na subfase 0.1): saldo no Leitor360 (já no modal do modo servidor), página de saldo e extrato, compra avulsa e alerta de saldo baixo.

A interface mostra o saldo comercial total, mesmo em contingência (ADR-0061 §6).

## 8. Riscos e decisões pendentes do responsável

O responsável respondeu as decisões D-1 a D-9 em 11/11/2026. O registro da sessão está no [histórico](../../historico/2026-11-11-decisoes-billing.md). D-2, D-3 e D-4 continuam abertas.

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| D-1 Titular do saldo | (a) Professor; (b) escola; (c) núcleo; (d) conforme o eixo da assinatura | — | **Decidida (11/11/2026):** cada operação tem um único contexto de cobrança (professor, escola ou núcleo), sem recurso automático a outro contexto. O crédito pessoal não financia operação institucional. Modelo no [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §4 |
| D-2 Quem da escola ou do núcleo pode consumir o saldo daquele contexto | (a) Todo professor do escopo; (b) só quem o responsável autoriza; (c) cota por professor | (a) com permissão própria, e (c) como evolução | **Pendente** |
| D-3 Quantidade de franquia por plano | Quantidade por plano e ciclo de renovação (mensal, anual) | Definir junto com os preços da FN-068, na mesma sessão | **Pendente** |
| D-4 Formato da compra avulsa | Pacotes fixos ou quantidade livre; preço por crédito | Pacotes fixos | **Pendente** |
| D-5 Validade | (a) Não vence; (b) o incluso vence e o avulso não; (c) tudo vence | — | **Decidida (11/11/2026):** comprado sem expiração por padrão; promocional e recompensa com validade por política; franquia vence no fim do ciclo (ADR-0061 §5) |
| D-6 Correção recusada pela barreira de qualidade | (a) Não cobra; (b) cobra, porque o servidor processou | — | **Decidida (11/11/2026):** (a) não cobra. Imagem inutilizável, página faltando, estrutura não reconhecida e processamento interrompido geram `RELEASE` (§6.4) |
| D-7 Transferência e compartilhamento | (a) Proibidos; (b) permitidos dentro da organização | — | **Decidida (11/11/2026):** (a) proibidos. Créditos não são sacáveis, conversíveis em dinheiro, transferíveis nem negociáveis. O núcleo delega franquia à escola por alocação explícita, o que não é transferência de crédito (ADR-0061 §4 e §5) |
| D-8 Recompensas por contribuição | (a) Mesma moeda dos créditos de correção; (b) moeda separada | — | **Decidida (11/11/2026):** (a) mesma moeda, origem `REWARD`, na carteira pessoal de quem contribuiu (§6.6) |
| D-9 Estorno e reembolso | Quando o crédito volta | — | **Decidida em parte (11/11/2026):** `REVERSAL` devolve à origem consumida, com a validade original; se a origem já venceu, o sistema compensa com um lote `PROMOTIONAL` (ADR-0061 §5). ⚠ O reembolso em dinheiro depende da pendência jurídica 1, na [FN-068 §8](../068-financeiro/planejamento.md) |
| ⚠ Entidade única de aluno + aplicação (`correction_subject_id`) | — | — | **Pendente técnica.** Opções e recomendação na [FN-068 §8](../068-financeiro/planejamento.md) |

| Risco | Efeito | Mitigação |
|---|---|---|
| Consumo duplo em concorrência | A mesma submissão cobra duas vezes | `UNIQUE(product, resource, operation_key)` e no máximo um `COMMIT` por operação; testes A e D do §6.7 |
| `correction_subject_id` mal escolhido | A cobrança passa a ser por prova, e não por aluno em uma aplicação | Resolver a pendência técnica antes da subfase 1.1; nunca usar o id da prova |
| Saldo zerado no meio de um lote | Parte do lote fica sem correção | Reserva antes de entrar na fila; o lote mostra quantos créditos vai usar antes de enviar (proposta) |
| Custo real maior que o preço | Fila longa e GPU (FN-080 §8.3) | Medir o custo por correção na subfase 6.3 da FN-079 antes de fixar o preço |
| Reserva presa por worker morto | Crédito indisponível sem consumo | Heartbeat do worker e expiração da reserva; teste E do §6.7 |

## 9. Ramos desta entrega

Nenhum ainda.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-11 | Criado como candidata, com as regras já decididas no Leitor360 e as perguntas da próxima sessão | lucasgenial |
| 2026-11-11 | Decisões de billing de 11/11/2026 incorporadas: identidade por `correction_subject_id` no lugar de "aluno × prova"; reenvio nunca cobra; regras de reserva, `COMMIT` e `RELEASE`; respostas a D-1, D-5, D-6, D-7, D-8 e D-9; créditos por contribuição validada no escopo. A arquitetura passou ao [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) | lucasgenial |
