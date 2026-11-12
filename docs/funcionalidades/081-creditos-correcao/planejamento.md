# 081 — Créditos de correção — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Conforme princípios:** versão 19
**Posição no plano mestre:** fora das ondas (candidata; próxima documentação, aguarda priorização do responsável)
**Natureza:** nova
**Origem:** surgiu no planejamento do Leitor360 (FN-079) e do Escriba (FN-080) em 2026-11-11, com o modo servidor
**Atualizado em:** 2026-11-12, por lucasgenial

Planejamento preliminar. Ele reúne o que já está decidido e as perguntas que continuam abertas. Nada é executado antes da priorização e da aprovação destes documentos.

Neste documento, "(proposta)" significa: proposta do planejamento, a confirmar pelo responsável. "⚠" marca o que ainda não foi confirmado.

Esta funcionalidade é **dona das regras funcionais dos créditos de correção, do ponto de vista do produto**: unidade, identidade, momento do consumo, reenvio e o que o Gabarito360 informa ao financeiro.

A autoridade financeira (saldo, carteira, franquia, reserva, contabilização e auditoria) é do aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)). O contrato de integração é do [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). A porta `BillingClient`, o simulador e a experiência financeira limitada são da [FN-068](../068-financeiro/planejamento.md). O Gabarito360 não tem carteira, ledger nem motor financeiro local.

Marcas de origem, quando usadas: **[Diretriz]**, **[Histórico]**, **[Proposto]** e **[Pendente]** ([contrato](../../arquitetura/14-integracao-financeira.md) §0).

## 1. Objetivo

O processamento no aparelho do usuário não custa nada ao Gabarito360. O processamento no servidor custa. Esta funcionalidade controla esse custo com créditos de correção. Ela identifica cada submissão e pede ao aplicativo financeiro a reserva, a confirmação ou a liberação do consumo. Ela recusa o envio sem autorização e mostra o extrato recebido do aplicativo financeiro.

## 2. Escopo

- **Inclui:**
  - Consumo do recurso tarifado `correction.server.student`, ao preço histórico de 1 crédito por submissão. O preço vigente vem do aplicativo financeiro.
  - Identidade da cobrança por `correction_subject_id` (um aluno em uma aplicação de prova). O Gabarito360 pede reserva, confirmação (`COMMIT`) e liberação (`RELEASE`) pela porta `BillingClient`.
  - Idempotência: o mesmo `correction_subject_id` nunca cobra duas vezes, qualquer que seja o número de páginas, de reenvios ou de mensagens repetidas da fila.
  - Recusa do envio sem autorização do aplicativo financeiro (sem franquia e sem crédito, limite do professor atingido na capacidade limitada ou serviço indisponível), com mensagem. No uso livre monitorado, a referência de acompanhamento não recusa o envio (§6.2, item 10a).
  - Aviso prévio de consumo no Leitor360 (o texto já está decidido na FN-079).
  - Registro operacional da correção (identidade, correlação e resultado da integração). Ele não é débito financeiro.
  - Exibição do saldo e do extrato de consumo recebidos do aplicativo financeiro, no contexto de cobrança da operação.
  - Requisitos do produto para a franquia do plano e a compra avulsa, executadas pelo aplicativo financeiro (quantidades e formato: D-3 e D-4, abertas).
  - Créditos por contribuição validada (programa de recompensa do professor), na mesma moeda. O Gabarito360 informa a contribuição validada; o aplicativo financeiro concede.
  - Alerta de saldo baixo. **[Proposto]** Só para capacidade limitada e plano pessoal, a partir de dado recebido do aplicativo externo. Nunca no uso livre ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3).
- **Não inclui:**
  - Carteira, ledger, lotes, saldo, preços, franquias, reservas, contabilização, reversões e auditoria financeira → aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)).
  - Porta `BillingClient`, cliente da API, simulador de integração e experiência financeira limitada → [FN-068](../068-financeiro/planejamento.md).
  - Capacidades, respostas, idempotência e falhas da integração → [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md).
  - O modo servidor, a fila e o marcador de identificação → [FN-079](../079-leitor360/planejamento.md) §6.12.
  - O Escriba no servidor → [FN-080](../080-ia-local-transcricao/planejamento.md) §8.3.
  - Aviso ao terminar a fila → [FN-057](../057-notificacoes/planejamento.md).
  - A curadoria das questões enviadas ao acervo, que antecede a recompensa → [FN-044](../044-acervo-gii/planejamento.md). O acervo não tem autoridade sobre créditos.
  - O aplicativo financeiro, os gateways e a NFSe → fora deste repositório (ADR-0061).

## 3. Fontes de verdade

- Financeiro externo: [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md). Contrato: [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md).
- Identidade da cobrança e modo servidor: [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), item 8; [FN-079 §6.12 e §8](../079-leitor360/planejamento.md); [FN-080 §8.3](../080-ia-local-transcricao/planejamento.md).
- Registro histórico da sessão de decisão de 11/11/2026: [Decisões de billing, créditos e M3 Core](../../historico/2026-11-11-decisoes-billing.md). Ele não é norma.
- Telas: mockup aprovado `mockups/leitor360.html`, tela H (`data-tela="sem-ia"`) e modal "Usar a correção no servidor". As telas próprias de saldo e extrato passam a ser de **Plano e uso**, com mockup na [FN-068](../068-financeiro/planejamento.md), subfase 2.3 ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)). A compra de créditos acontece na Central do cliente, no aplicativo externo (FN-068 UC-068-19 e UC-068-20) ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md) e [ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)).
- Financeiro: [FN-068](../068-financeiro/planejamento.md).
- Perfis e permissões: [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| ADR-0061 financeiro externo | Define a fronteira e proíbe o financeiro local | Aceita (revisada em 12/11/2026) |
| Contrato de integração ([14](../../arquitetura/14-integracao-financeira.md)) | Capacidades, respostas, idempotência e falhas | Proposto; protocolo pendente |
| FN-068 financeiro | Porta `BillingClient`, simulador e experiência financeira limitada | Em planejamento |
| Aplicativo financeiro externo | Autoriza, reserva e contabiliza o consumo | **Não existe.** Dependência externa; os testes usam o simulador |
| Entidade de aplicação e submissão | Dá a identidade `correction_subject_id` (§8) | Pendente técnica |
| FN-079 leitor360 | Estados da submissão que disparam `RESERVE`, `COMMIT` e `RELEASE` | Planejamento aprovado |
| FN-004 auditoria | Trilha de concessões e consumos | Parcial |
| FN-057 notificacoes | Alerta de saldo baixo **[Proposto]**: só capacidade limitada e plano pessoal; nunca no uso livre | Em planejamento |

**Funcionalidades que dependem desta:** FN-079 (subfase 4.3, modo servidor das objetivas) e FN-080 / FN-079 6.3 (Escriba no servidor). Para desenvolvimento e homologação, elas precisam das subfases 1.0 e 1.1 desta FN, com o simulador. A ativação comercial do modo servidor exige a subfase 3.1 e os requisitos de ativação comercial do §5.

## 5. Fases e subfases (preliminares)

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Planejamento, casos de uso e mockups aprovados para desenvolvimento e homologação | O responsável aprova os documentos e o mockup de referência, que passa a ser o de **Plano e uso** ([FN-068](../068-financeiro/planejamento.md), subfase 2.3). A aprovação dele **não prioriza a implementação da FN-081** (decisão do responsável de 12/11/2026, no [plano mestre](../../planejamento/00-plano-mestre.md) §4.1). **D-3, D-4 e os detalhes comerciais da D-2 não bloqueiam esta subfase** | Pendente | |
| 1.0 | Identidade da submissão (`correction_subject_id`) | A pendência técnica do §8 está resolvida, com unicidade de aluno + aplicação no banco do Gabarito360 | Pendente | |
| 1.1 | Ciclo de consumo da correção pela porta `BillingClient`, contra o simulador genérico (FN-068 1.1), com processamento educacional real | Os testes A e C a I do §6.7 passam; um teste de ponta a ponta vai do envio à conferência com o simulador; a concorrência no MariaDB não gera dois pedidos de consumo definitivo; nenhuma migration financeira | Pendente | |
| 1.2 | Consumo integrado ao modo servidor (FN-079 4.3) | Sem franquia e sem crédito, o envio é recusado antes de entrar na fila; o `COMMIT` só ocorre em `READY_FOR_REVIEW` | Pendente | |
| 2.1 | Telas de saldo e extrato (em Plano e uso; entrega coordenada com a FN-068, subfase 2.4) | Conforme mockup aprovado; dados recebidos do aplicativo financeiro; responsivo em todas as faixas (ADR-0057) | Pendente | |
| 3.1 | Integração com o aplicativo financeiro real (franquia, compra avulsa e limites por professor executados por ele) | O aplicativo financeiro existe; a FN-068 1.2 está entregue; o teste B do §6.7 segue o protocolo aprovado | Pendente (bloqueada pela dependência externa) | |
| 4.1 | Créditos por contribuição validada | O Gabarito360 informa só a contribuição aprovada pela curadoria; o aplicativo financeiro concede na carteira pessoal de quem contribuiu | Pendente | |

### 5.1 Requisitos de teste e requisitos de ativação comercial

**[Diretriz]** A ausência do aplicativo financeiro não bloqueia o desenvolvimento nem a homologação. Os requisitos se dividem assim.

| Requisito | Desenvolvimento e homologação (com o simulador) | Ativação comercial |
|---|---|---|
| Identidade aluno + aplicação (`correction_subject_id`, subfase 1.0) | **Bloqueia.** É dependência técnica real | Bloqueia |
| Porta `BillingClient` e simulador genérico (FN-068 1.1) | Bloqueia | Bloqueia |
| Aplicativo financeiro real e cliente da API (FN-068 1.2) | Não bloqueia | Bloqueia |
| D-3 (quantidades por plano) | Não bloqueia | Bloqueia |
| D-4 (formato da compra avulsa) | Não bloqueia | Bloqueia |
| Detalhes comerciais da D-2 (quem define limites, padrão e fim do ciclo) | Não bloqueia; o simulador cobre os cenários de limite | Bloqueia |
| Pendências jurídicas 1 e 2 | Não bloqueiam | Bloqueiam as operações comerciais e de distribuição de conteúdo que dependem delas |
| Protocolo de contingência | Não bloqueia; o simulador cobre timeout e indisponibilidade | Bloqueia a contingência; sem protocolo, a falha só recusa |

Os valores usados nos testes (saldo, limites e quantidades fictícios) não definem preços, planos nem cotas.

## 6. Contrato técnico

Toda consulta e todo ciclo de consumo passam pela porta `BillingClient`, segundo o [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). O Gabarito360 não grava saldo. A ordem de consumo, as origens dos lotes, os tipos de transação e a regra append-only são requisitos históricos do aplicativo financeiro ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisão de 12/11/2026). Os itens abaixo são as regras próprias da correção. **[Histórico]**, salvo marca.

### 6.1 Unidade e identidade da cobrança

1. **Recurso e preço.** O recurso tarifado é `correction.server.student`. O valor histórico definido é 1 crédito, ou a unidade correspondente da franquia. O preço fica no catálogo do aplicativo financeiro, nunca no código do Gabarito360.
2. **Unidade.** Uma submissão de um aluno em uma aplicação de prova, qualquer que seja o número de páginas.
3. **Identidade.** `operation_key = correction:{correction_subject_id}`. O `correction_subject_id` identifica de forma única o par aluno + aplicação.
   - ⚠ **Pendência técnica:** qual entidade representa esse par de forma única. **Nunca use só o id da prova.** Evidência, opções e recomendação no §8.
4. **No máximo um consumo definitivo.** Uma operação pode ter várias tentativas e reservas, mas **no máximo um `COMMIT`**. O aplicativo financeiro garante essa regra pela chave da operação (contrato §6). O desenho de 11/11/2026 previa `UNIQUE(product, resource, operation_key)` numa tabela local `billing_operations`; essa tabela foi superada.
5. **A identidade é permanente.** Reenvio, nova foto e recálculo por alteração de gabarito não geram operação nova e não cobram. O hash da imagem não é identidade de cobrança.
6. **Cobrança nova só em aplicação ou tentativa real nova** (recuperação, segunda chamada, nova aplicação). Ela gera um `correction_subject_id` novo.
7. **Só no servidor.** O processamento no aparelho nunca consome crédito. A chave `leitura.transcrever` não cobra no aparelho.

### 6.2 Reserva

8. A reserva existe apenas no trecho envio → fila → processamento. Ela fica no aplicativo financeiro. O worker do Gabarito360 pede a renovação enquanto processa (o desenho histórico usava heartbeat).
9. O tempo de vida (TTL) da reserva é configuração operacional, não regra de negócio.
10. Sem autorização do aplicativo financeiro para o contexto de cobrança, o envio é recusado antes de entrar na fila. A queda da API financeira nunca autoriza o envio nem ativa o simulador. **[Diretriz]**

    10a. **Limite bloqueante por professor: só na capacidade limitada.** **[Diretriz]** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3; [contrato](../../arquitetura/14-integracao-financeira.md) §4, item 9; D-2 decidida em parte, §8)

    - **Alcance.** A regra vale só na modalidade e na política que preveem o limite: a instituição com capacidade limitada. Ela não vale em todas as modalidades.
    - **Capacidade limitada.** O financeiro institucional autorizado define e administra o limite mensal por professor na Central do cliente, no aplicativo externo. O aplicativo financeiro aplica o limite. Ao atingir o limite, o envio é recusado, mesmo com saldo na instituição. A relação entre o limite e o saldo institucional está no [contrato](../../arquitetura/14-integracao-financeira.md) §4, item 9.
    - **Uso livre monitorado.** A referência de acompanhamento não é esse limite. O Gabarito360 não recusa o envio por ela e não infere bloqueio nem esgotamento. Só o Financeiro do aplicativo externo ajusta, depois de conferir o contrato. **[Pendente]** O comportamento além da referência.
    - **Todas as modalidades.** O item 10 continua valendo: sem autorização do aplicativo financeiro, o envio é recusado. O uso livre não dispensa essa autorização.
    - **[Pendente]** Quem é o financeiro institucional autorizado em cada contexto, o padrão para professor sem limite definido e o fim do ciclo (§8, D-2).

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

17. O Gabarito360 guarda o registro operacional da submissão: `correction_subject_id`, chave da operação, referência externa, último resultado da integração e estados da correção. Esse registro não é débito financeiro nem fonte de saldo.
18. Requisitos de rastreabilidade do aplicativo financeiro, preservados do desenho de 11/11/2026: um evento lógico de consumo por operação, qualquer que seja a fonte; movimento da carteira só quando ela é a fonte; consumo de franquia auditável; registros append-only; compensação, reversão e preço histórico da operação. O Gabarito360 não implementa esses ledgers.
19. **Dados de aluno (condição B4, [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §5).** **[Diretriz]** O aplicativo externo não recebe nem devolve nome de aluno, resposta, nota ou outro detalhe pedagógico. A linha de consumo traz só a referência opaca `correction:{correction_subject_id}`. O Gabarito360 resolve aluno e aplicação no próprio banco, no momento do pedido. Ele exige duas condições juntas: o `Escopo` do vínculo ativo e uma permissão **existente cuja proteção esteja demonstrada**. Sem a demonstração, ou na dúvida, a resposta da API traz "Dado restrito" no lugar do aluno e da aplicação, e a linha de consumo continua visível. Nenhuma chave é escolhida por "parecer restritiva". "Acesso à prova" sozinho não basta: o Aplicador tem `prova.ver`. **A chave exata é [Pendente]**, até a demonstração. O critério da demonstração está na [FN-068 §6.4.5](../068-financeiro/planejamento.md). O perfil interno `financeiro` não tem tela em Plano e uso: a visão de vários clientes é do aplicativo externo ([FN-068](../068-financeiro/planejamento.md) §8).

### 6.6 Créditos por contribuição validada

20. O nome oficial do programa é "Créditos por contribuição validada". Ele não é gamificação. Ele não é a mecânica vedada pelo ECA Digital em [11 Segurança e privacidade](../../arquitetura/11-seguranca-e-privacidade.md) (item 5 dos pontos jurídicos), que trata das telas de alunos. **O aluno não participa.**
21. O crédito só nasce depois da validação ou da curadoria aprovada. Enviar uma contribuição não gera crédito.
22. Fluxo: o professor contribui; a contribuição é validada no Gabarito360; o Gabarito360 informa o fato ao aplicativo financeiro; a regra do aplicativo financeiro calcula e registra a concessão (`GRANT`, origem `REWARD`) na carteira. A recompensa não altera a autoria contextual nem resolve a licença do conteúdo.
23. O crédito vai para a carteira **pessoal** de quem contribuiu, em qualquer contexto em que a pessoa contribuiu. É intencional que um professor só institucional acumule saldo pessoal. Esse saldo não pode ser transferido para a escola.
24. ⚠ A titularidade, a licença de uso e a autorização de envio das questões ao acervo dependem da pendência jurídica 2, registrada na [FN-068 §8](../068-financeiro/planejamento.md).

### 6.7 Testes obrigatórios

| # | Teste | Resultado esperado |
|---|---|---|
| A | Reenvio de foto do mesmo `correction_subject_id` | Mesmo `operation_key`; nenhuma cobrança adicional |
| B | Contingência com concessão válida e depois reconciliação (protocolo pendente, contrato §10) | Sem duplicidade; nenhum saldo negativo; sem concessão válida, nenhuma autorização |
| C | Alterar o gabarito depois da correção e recalcular | Nenhum crédito adicional |
| D | A mesma mensagem da fila entregue duas vezes | Um único `COMMIT` |
| E | O worker morre depois do `RESERVE` e antes do resultado | A reserva expira e libera; nenhum crédito é perdido |
| F | A API financeira não responde no envio | O envio é recusado; o simulador não é ativado; nada é autorizado |
| G | Capacidade limitada: professor no limite mensal, com saldo na instituição | O envio é recusado; nenhum crédito passa à carteira pessoal |
| H | Consumo feito com o simulador | Nenhuma cobrança real nem dívida retroativa; as permissões educacionais não mudam |
| I | Uso livre monitorado: consumo acima da referência de acompanhamento | O Gabarito360 não recusa nem bloqueia por conta própria e não mostra esgotamento; o envio segue só a resposta do aplicativo financeiro (item 10). Este teste não decide o comportamento além da referência (**[Pendente]**, item 10a) |

Os testes A e C a I rodam contra o simulador genérico (FN-068 1.1), com processamento educacional real: só a resposta financeira é simulada. O teste B depende do protocolo de contingência.

O teste I não tem cenário próprio no simulador. Um cenário de uso livre decidiria o comportamento além da referência, que está **[Pendente]**. O teste I usa dois cenários existentes do [contrato](../../arquitetura/14-integracao-financeira.md) §9.2, numa configuração de uso livre monitorado:

- **Autorizado (cenário 1).** O envio segue.
- **Permissão ou contexto recusado (cenário 5).** O envio é recusado pelo item 10.

Nos dois cenários, a tela não mostra esgotamento.

## 7. Interface

O app é responsivo ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)). Cada tela e cada modal desta funcionalidade é conferido nas larguras 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro, com registro por texto ([ADR-0049](../../decisoes/0049-sem-prints-da-aplicacao.md)).

Regra do mockup ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md)): cada tela usa a casca, os componentes e o CSS reais de `apps/web`, e o design system de `style-system/`.

Telas previstas: saldo ou franquia no Leitor360 (já no modal do modo servidor), aviso de limite do professor e alerta de saldo baixo. O aviso de limite do professor existe só na capacidade limitada, onde o limite bloqueante vale (item 10a). O alerta de saldo baixo é **[Proposto]**, só para capacidade limitada e plano pessoal. No uso livre monitorado, não há aviso de limite, alerta de saldo baixo nem indicador de esgotamento ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3). As telas de saldo e extrato são de **Plano e uso**, com mockup na [FN-068](../068-financeiro/planejamento.md), subfase 2.3. A compra avulsa e a gestão de limites são ações humanas da Central do cliente, no aplicativo externo ([FN-068 §8](../068-financeiro/planejamento.md); [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)).

A interface mostra o saldo, a franquia ou o consumo recebidos do aplicativo financeiro, conforme a modalidade. Ela não calcula saldo, percentual nem esgotamento. A exibição é temporária: o aparelho não persiste dados financeiros (ADR-0061). O desenho histórico mostrava o saldo comercial total, mesmo em contingência.

## 8. Riscos e decisões pendentes do responsável

O responsável respondeu as decisões D-1 a D-9 em 11/11/2026. O registro da sessão está no [histórico](../../historico/2026-11-11-decisoes-billing.md). D-2 foi decidida em parte. D-3 e D-4 continuam abertas.

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| D-1 Titular do saldo | (a) Professor; (b) escola; (c) núcleo; (d) conforme o eixo da assinatura | — | **Decidida (11/11/2026):** cada operação tem um único contexto de cobrança (professor, escola ou núcleo), sem recurso automático a outro contexto. O crédito pessoal não financia operação institucional ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §5) |
| D-2 Quem da escola ou do núcleo pode consumir o saldo daquele contexto | (a) Todo professor do escopo; (b) só quem o responsável autoriza; (c) cota por professor | — | **Decidida em parte (12/11/2026):** o responsável financeiro institucional compra créditos para a entidade e administra limites mensais de consumo por professor. Isso é governança do consumo, não transferência para a carteira pessoal. Renovar o limite não cria créditos. O aplicativo financeiro aplica o limite (§6.2, item 10a). **Refinada por modalidade (12/11/2026, [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3):** no **uso livre monitorado**, só o Financeiro do aplicativo externo ajusta, após conferir o contrato; a escola acompanha e pede ajustes, mas não redistribui. Na **capacidade limitada**, o financeiro institucional autorizado administra a capacidade na Central do cliente, dentro do contratado e dos adicionais confirmados. ⚠ **Pendente:** quem é o responsável financeiro em cada contexto (o perfil do representante institucional não está definido), o padrão para professor sem limite definido e o fim do ciclo. Esses detalhes não bloqueiam desenvolvimento nem homologação (§5.1) |
| D-3 Quantidade de franquia por plano | Quantidade por plano e ciclo de renovação (mensal, anual) | Definir junto com os preços, no aplicativo financeiro | **Pendente.** Não bloqueia desenvolvimento nem homologação (§5.1) |
| D-4 Formato da compra avulsa | Pacotes fixos ou quantidade livre; preço por crédito | Pacotes fixos | **Pendente.** Não bloqueia desenvolvimento nem homologação (§5.1) |
| D-5 Validade | (a) Não vence; (b) o incluso vence e o avulso não; (c) tudo vence | — | **Decidida (11/11/2026):** comprado sem expiração por padrão; promocional e recompensa com validade por política; franquia vence no fim do ciclo. Requisito do aplicativo financeiro |
| D-6 Correção recusada pela barreira de qualidade | (a) Não cobra; (b) cobra, porque o servidor processou | — | **Decidida (11/11/2026):** (a) não cobra. Imagem inutilizável, página faltando, estrutura não reconhecida e processamento interrompido geram `RELEASE` (§6.4) |
| D-7 Transferência e compartilhamento | (a) Proibidos; (b) permitidos dentro da organização | — | **Decidida (11/11/2026):** (a) proibidos. Créditos não são sacáveis, conversíveis em dinheiro, transferíveis nem negociáveis. O núcleo delega franquia à escola por alocação explícita, o que não é transferência de crédito (ADR-0061 §5) |
| D-8 Recompensas por contribuição | (a) Mesma moeda dos créditos de correção; (b) moeda separada | — | **Decidida (11/11/2026):** (a) mesma moeda, origem `REWARD`, na carteira pessoal de quem contribuiu (§6.6) |
| D-9 Estorno e reembolso | Quando o crédito volta | — | **Decidida em parte (11/11/2026):** `REVERSAL` devolve à origem consumida, com a validade original; se a origem já venceu, o aplicativo financeiro compensa com um lote `PROMOTIONAL`. Requisito do aplicativo financeiro. ⚠ O reembolso em dinheiro depende da pendência jurídica 1, na [FN-068 §8](../068-financeiro/planejamento.md) |
| ⚠ Entidade única de aluno + aplicação (`correction_subject_id`) | (a) Usar o id do cartão; (b) criar uma entidade de submissão da correção; (c) chave composta de aplicação e aluno, persistida | (b) ou (c), com a aplicação como entidade própria. **Evidência (conferida em 12/11/2026 em `apps/api/database/migrations`):** `cartoes` só tem índices simples em `prova_id` e `aluno_id`, sem unicidade do par; as tentativas ficam em `cartao_tentativas` (`2026_11_05_000001`); não existe tabela nem model de aplicação; `provas.data_aplicacao` é só uma coluna de data. Logo, (a) não serve hoje. O usuário escolheu a identidade de negócio aplicação + aluno; o formato concreto segue a modelagem do projeto. Um UUID persistido não gera identidade nova a cada reenvio. A entidade de aplicação se relaciona com a [FN-059](../059-agendamento-aplicacao-provas/planejamento.md). **Aplicação não é o perfil Aplicador:** criar a identidade mínima não exige agendamento completo nem perfil novo, e não revoga o [ADR-0059](../../decisoes/0059-aplicador-ve-a-leitura-sem-corrigir.md). **Nunca usar só o id da prova** | **Pendente técnica** |
| ⚠ Necessidade do perfil Aplicador | Manter, adiar ou rever | Adiar foi recomendado em 11/11/2026. A recomendação não autoriza revogar o ADR-0059 nem remover o perfil | **Pendente** (decisão do responsável) |

| Risco | Efeito | Mitigação |
|---|---|---|
| Consumo duplo em concorrência | A mesma submissão cobra duas vezes | Chave estável da operação e no máximo um `COMMIT` no aplicativo financeiro; unicidade da submissão no Gabarito360; testes A e D do §6.7 |
| `correction_subject_id` mal escolhido | A cobrança passa a ser por prova, e não por aluno em uma aplicação | Resolver a pendência técnica antes da subfase 1.1; nunca usar o id da prova |
| Saldo zerado ou limite atingido no meio de um lote | Parte do lote fica sem correção | Reserva antes de entrar na fila; o lote mostra quantos créditos vai usar antes de enviar (proposta) |
| Financeiro local recriado "temporariamente" | Duas verdades sobre saldo | Proibição do ADR-0061; o registro operacional não guarda saldo |
| Queda da API financeira | O modo servidor fica indisponível | Recusa clara; leitura no aparelho e revisão manual continuam (teste F) |
| Custo real maior que o preço | Fila longa e GPU (FN-080 §8.3) | Medir o custo por correção na subfase 6.3 da FN-079 antes de fixar o preço |
| Reserva presa por worker morto | Crédito indisponível sem consumo | Heartbeat do worker e expiração da reserva; teste E do §6.7 |

## 9. Ramos desta entrega

Nenhum ainda.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-11 | Criado como candidata, com as regras já decididas no Leitor360 e as perguntas da próxima sessão | lucasgenial |
| 2026-11-11 | Decisões de billing de 11/11/2026 incorporadas: identidade por `correction_subject_id` no lugar de "aluno × prova"; reenvio nunca cobra; regras de reserva, `COMMIT` e `RELEASE`; respostas a D-1, D-5, D-6, D-7, D-8 e D-9; créditos por contribuição validada no escopo. A arquitetura passou ao [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) | lucasgenial |
| 2026-11-12 | Reorientação para o aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisão de 12/11/2026): a autoridade sobre saldo, reserva e contabilização passou ao aplicativo financeiro; o Gabarito360 guarda só o registro operacional; regras da correção preservadas; D-2 decidida em parte (limites mensais por professor); testes F, G e H; evidência da pendência aluno + aplicação trazida para o §8 | lucasgenial |
| 2026-11-12 | §5.1: requisitos de teste separados dos de ativação comercial. D-3, D-4 e os detalhes comerciais da D-2 não bloqueiam desenvolvimento e homologação; a identidade aluno + aplicação continua dependência técnica real. Subfase 1.1 com o simulador genérico e processamento real | lucasgenial |
| 2026-11-12 | Regra do aparelho alinhada ao ADR-0061: exibição temporária, sem persistir dados financeiros | lucasgenial |
| 2026-11-12 | Adequação derivada da reorientação da FN-068 ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)): telas de saldo e extrato passam a Plano e uso (FN-068 2.3); mockup de referência da subfase 0.1 trocado, sem priorizar a implementação desta FN (plano mestre §4.1); D-2 refinada por modalidade; frase sobre a visão de totais do perfil Financeiro trocada (B3). Revisão independente da subfase 2.2: regra B4 completa no §6.5, item 19; casos de uso citados corrigidos (UC-068-19 e UC-068-20); alerta de saldo baixo marcado **[Proposto]**, nunca no uso livre. Regras de consumo inalteradas. "Conforme princípios" fica na versão 18 | lucasgenial |
| 2026-11-12 | Revalidação de acesso, dados pessoais e privacidade por `seguranca-privacidade` na segunda leitura ("Pode subir", independente de agente, não de fornecedor); `qualidade-testes` aprovou com ressalvas (meios de prova da UC-081-09 corrigidos). "Conforme princípios" subiu de 18 para 19, versão que o ADR-0062 gera. Esta FN não tem subfase de adequação no §5. Prioridade e etapa inalteradas: a revalidação não prioriza a implementação | lucasgenial |
| 2026-11-12 | Ajuste da revisão do responsável: item 10a (§6.2) limita o bloqueio por professor à capacidade limitada; o item 10 vale em todas as modalidades e a referência do uso livre não é esse limite. Teste I (§6.7) cobre o uso livre acima da referência. Remissões ao item 10a no escopo (§2) e no §7. D-2 decidida em parte e D-3 e D-4 abertas, na abertura do §8. "Conforme princípios" continua na versão 19 | lucasgenial |
