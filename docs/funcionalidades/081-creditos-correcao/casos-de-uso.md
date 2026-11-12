# 081 — Créditos de correção — Casos de uso

**Natureza:** nova (candidata; casos de uso preliminares de alto nível)
**Perfis envolvidos:** professor, coordenação e direção que corrigem pelo Leitor360 no modo servidor; responsável pelo contexto de cobrança (professor, escola ou núcleo); responsável financeiro institucional (limites por professor); perfil Financeiro (só totais); aplicativo financeiro (sistema externo). Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
**Atualizado em:** 2026-11-12

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Nenhum caso está implementado. As regras estão no [planejamento §6](planejamento.md). As decisões abertas estão no [planejamento §8](planejamento.md).

O saldo, a carteira, a franquia, a reserva e a contabilização ficam no aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)). O Gabarito360 pede e registra o resultado pela porta `BillingClient`, segundo o [contrato](../../arquitetura/14-integracao-financeira.md). Até o aplicativo financeiro existir, os testes usam o simulador de integração.

Termos usados aqui (um termo por conceito):

| Termo | Significado |
|---|---|
| Submissão | Um aluno em uma aplicação de prova, identificado por `correction_subject_id`, com todas as páginas dele |
| Operação | O registro de cobrança da submissão, com `operation_key = correction:{correction_subject_id}` |
| Reserva | Capacidade retida enquanto a submissão está em envio, fila ou processamento |
| `COMMIT` | Confirmação do consumo, quando a submissão chega a `READY_FOR_REVIEW` |
| `RELEASE` | Liberação da reserva, sem cobrança |
| Aplicativo financeiro | Aplicativo externo independente, dono do saldo, da reserva e da contabilização |
| Registro operacional | O que o Gabarito360 guarda da submissão: identidade, chave da operação, referência externa e último resultado. Não é débito |

---

## UC-081-01 — Consumir um crédito numa correção no servidor

**Ator:** usuário que corrige no modo servidor (FN-079 UC-079-26)
**Permissão:** `cartao.enviar`; permissão de consumo conferida pelo aplicativo financeiro (D-2, detalhes pendentes)
**Escopo:** prova no escopo do vínculo ativo; contexto de cobrança único da operação
**Pré-condições:** o usuário aceitou o aviso do modo servidor; o aplicativo financeiro autoriza o consumo no contexto de cobrança.
**Pós-condições:** 1 crédito consumido no aplicativo financeiro para o `correction_subject_id`; um evento de consumo gravado lá; registro operacional atualizado no Gabarito360.
**Tela/rota:** modal "Usar a correção no servidor" (`mockups/leitor360.html`); rota a definir.

**Fluxo principal:**
1. O usuário envia a primeira página de um aluno numa aplicação de prova.
2. O Gabarito360 registra a submissão e pede ao aplicativo financeiro a reserva da operação `correction:{correction_subject_id}`.
3. As outras páginas da mesma submissão usam a mesma operação e a mesma reserva.
4. A correção chega a `READY_FOR_REVIEW` com todos os critérios do planejamento §6.3 atendidos.
5. O Gabarito360 pede a confirmação do consumo (`COMMIT`). O aplicativo financeiro grava um único evento de consumo.

**Fluxos alternativos e de erro:**
- 2a. O aplicativo financeiro recusa (sem saldo, limite atingido ou sem permissão): UC-081-02.
- 4a. A submissão não atende aos critérios: UC-081-03 (`RELEASE`).
- 5a. A mesma mensagem da fila chega duas vezes: a mesma chave produz um único `COMMIT`.

**Regras de negócio:** planejamento §6.1 a §6.3 e §6.5.

**Critérios de aceite:**
- [ ] Três páginas do mesmo aluno na mesma aplicação consomem 1 crédito.
- [ ] Dois envios simultâneos da mesma submissão consomem 1 crédito (teste de concorrência).
- [ ] A mesma mensagem da fila entregue duas vezes gera um único `COMMIT` (teste D).
- [ ] Pendência de resposta individual (marcação dupla, ambígua ou não reconhecida) não impede a cobrança.
- [ ] O processamento no aparelho não gera operação nem evento de consumo.
- [ ] O Gabarito360 não grava saldo nem débito.

**Situação:** Não implementado.

---

## UC-081-02 — Recusar o envio sem autorização do aplicativo financeiro

**Ator:** usuário que corrige no modo servidor
**Permissão:** `cartao.enviar`
**Escopo:** contexto de cobrança da operação
**Pré-condições:** o aplicativo financeiro recusa a autorização, ou não responde.
**Pós-condições:** nada é enviado; nada é cobrado.
**Tela/rota:** Leitor360, modal da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)).

**Fluxo principal:**
1. O usuário tenta enviar.
2. O sistema recusa antes de a submissão entrar na fila.
3. O sistema mostra a recusa e, para quem pode, os caminhos: comprar créditos, pedir alocação de franquia ao núcleo ou alterar o plano. Essas ações acontecem no aplicativo financeiro ([FN-068](../068-financeiro/casos-de-uso.md), UC-068-13).

**Fluxos alternativos e de erro:**
- 2a. Limite mensal do professor atingido: o envio é recusado, mesmo com saldo na instituição (UC-081-11).
- 2b. O aplicativo financeiro não responde: o envio é recusado. O simulador não é ativado ([FN-068](../068-financeiro/casos-de-uso.md), UC-068-15).
- 2c. Existe concessão válida para contingência: UC-081-08 (protocolo pendente).

**Regras de negócio:** planejamento §6.2, itens 10 e 10a; [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §5 e §6.

**Critérios de aceite:**
- [ ] Sem saldo no contexto, nenhum envio entra na fila.
- [ ] O sistema não usa o saldo de outro contexto para cobrir a falta.
- [ ] A falta de saldo bloqueia só o recurso tarifado. O resto do sistema continua disponível conforme o plano.
- [ ] O aparelho com capacidade continua corrigindo sem créditos.
- [ ] A falha da API financeira não autoriza o envio (teste F).

**Situação:** Não implementado.

---

## UC-081-03 — Reenviar ou refazer a foto sem custo

**Ator:** usuário que corrige no modo servidor
**Permissão:** `cartao.enviar`
**Escopo:** a mesma submissão (`correction_subject_id`)
**Pré-condições:** a submissão já tem operação aberta.
**Pós-condições:** reenvio processado sem consumo novo.
**Tela/rota:** Leitor360, lote e leitor visual.

**Fluxo principal:**
1. O usuário reenvia a submissão, ou envia uma foto nova dela.
2. O sistema reconhece o mesmo `operation_key` e não abre operação nova.
3. Nenhum crédito adicional é consumido.

**Fluxos alternativos e de erro:**
- 1a. A submissão falhou (página faltando, imagem inutilizável, arquivo corrompido, estrutura não reconhecida, cartão não identificável ou processamento interrompido): o sistema faz `RELEASE` e leva a submissão a `NEEDS_RECAPTURE` ou `PROCESSING_FAILED`, sem cobrar.
- 1b. O usuário escolhe a revisão manual: nada é cobrado.
- 1c. O gabarito mudou e o resultado é recalculado: nenhum crédito adicional.

**Regras de negócio:** planejamento §6.1 itens 5 e 6, e §6.4.

**Critérios de aceite:**
- [ ] O reenvio não muda o saldo, com ou sem marca de dificuldade do servidor.
- [ ] A nova foto da mesma submissão não muda o saldo (teste A).
- [ ] O recálculo por alteração de gabarito não muda o saldo (teste C).
- [ ] Uma aplicação nova ou uma tentativa real nova (recuperação, segunda chamada) gera `correction_subject_id` novo e cobra 1 crédito.
- [ ] O hash da imagem não é usado como identidade de cobrança.

**Situação:** Não implementado.

---

## UC-081-04 — Marcar uma página como não entregue e fechar a submissão

**Ator:** professor responsável pela correção
**Permissão:** `cartao.enviar` e `correcao.alterar`
**Escopo:** a submissão
**Pré-condições:** falta uma página esperada da submissão.
**Pós-condições:** a submissão conta como completa; se atender aos demais critérios, chega a `READY_FOR_REVIEW` e consome 1 crédito.
**Tela/rota:** lote do Leitor360 (a desenhar na subfase 0.1).

**Fluxo principal:**
1. O professor confirma que a página esperada não existe e a marca como `NOT_DELIVERED`.
2. O sistema trata a submissão como completa.
3. Atendidos os demais critérios, a submissão chega a `READY_FOR_REVIEW` e o sistema confirma o consumo.

**Fluxos alternativos e de erro:**
- 1a. Sem a marca, a página faltando mantém a submissão incompleta e não cobra (UC-081-03, 1a).

**Regras de negócio:** planejamento §6.3 item 14.

**Critérios de aceite:**
- [ ] `NOT_DELIVERED` exige confirmação humana e fica registrado com autor e data.
- [ ] Página ausente sem a marca não cobra.
- [ ] A conferência humana depois (`READY_FOR_REVIEW` → `FINALIZED`) não muda a cobrança.

**Situação:** Não implementado.

---

## UC-081-05 — Comprar créditos avulsos

**Ator:** responsável financeiro do contexto; aplicativo financeiro
**Permissão:** identidade e permissão próprias no aplicativo financeiro
**Pré-condições:** D-4 decidida; lugar das ações humanas decidido ([FN-068 §8](../068-financeiro/planejamento.md)).
**Pós-condições:** o aplicativo financeiro registra um lote de origem `PURCHASED` no contexto, depois do pagamento confirmado. O Gabarito360 só mostra o novo saldo.

**Fluxo principal:**
1. O responsável escolhe um pacote.
2. O aplicativo financeiro gera a cobrança.
3. Com o pagamento confirmado, o aplicativo financeiro credita o lote.

**Fluxos alternativos e de erro:**
- 1a. O responsável financeiro institucional compra para a entidade. O lote entra no contexto da instituição, nunca na carteira pessoal.
- 3a. Evento repetido: o aplicativo financeiro credita uma única vez.

**Regras de negócio:** planejamento §8 (D-4); [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §1 e §5. Requisitos do aplicativo financeiro.

**Critérios de aceite:**
- [ ] Nenhum crédito entra antes da confirmação do pagamento.
- [ ] O evento repetido do gateway não credita duas vezes.
- [ ] A credencial técnica do Gabarito360 não consegue comprar nem creditar.
- [ ] O lote comprado não expira por padrão.
- [ ] ⚠ O direito de arrependimento e o reembolso dependem da pendência jurídica 1, na [FN-068 §8](../068-financeiro/planejamento.md).

**Situação:** Não implementado.

---

## UC-081-06 — Renovar a franquia do plano

**Ator:** aplicativo financeiro
**Pré-condições:** D-3 decidida; assinatura ativa no aplicativo financeiro.
**Pós-condições:** a franquia do ciclo novo fica disponível.

**Fluxo principal:**
1. No início do ciclo do plano, o aplicativo financeiro gera a franquia do recurso.
2. A franquia não usada do ciclo anterior vence.

**Fluxos alternativos e de erro:**
- 1a. Assinatura em `past_due`: a renovação segue a regra do aplicativo financeiro. `past_due` não implica bloqueio total imediato (histórico de 11/11/2026).

**Regras de negócio:** [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisão de 12/11/2026 (requisitos do aplicativo financeiro). O Gabarito360 só vê o resultado.

**Critérios de aceite:**
- [ ] A renovação roda uma vez por ciclo (idempotente).
- [ ] A franquia é consumida antes da carteira.
- [ ] A franquia não entra na carteira e não vira crédito.

**Situação:** Não implementado.

---

## UC-081-07 — Alocar franquia do núcleo para uma escola

**Ator:** responsável pelo núcleo
**Permissão:** identidade e permissão próprias no aplicativo financeiro
**Escopo:** núcleo e escola do mesmo cliente
**Pré-condições:** o núcleo tem franquia disponível no ciclo.
**Pós-condições:** a escola passa a ter franquia própria, delegada e registrada.

**Fluxo principal:**
1. O responsável pelo núcleo escolhe a escola e a quantidade.
2. O aplicativo financeiro registra a alocação e reduz a franquia disponível do núcleo.
3. A escola consome a franquia alocada no contexto dela.

**Fluxos alternativos e de erro:**
- 1a. Sem franquia disponível no núcleo: a alocação é recusada.

**Regras de negócio:** [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §5. A alocação é delegação de franquia, não transferência de créditos.

**Critérios de aceite:**
- [ ] A escola nunca consome a franquia do núcleo sem alocação explícita.
- [ ] A alocação registra quem alocou, quando, para quem e quanto.
- [ ] Nenhum crédito da carteira é transferido entre contextos.

**Situação:** Não implementado.

---

## UC-081-08 — Consumir em contingência e reconciliar

> ⚠ **Protocolo pendente** ([contrato](../../arquitetura/14-integracao-financeira.md) §10). Este caso guarda a intenção do desenho de 11/11/2026. O lease guardado no banco do Gabarito360, com evento de consumo local e outbox, foi superado (ADR-0061, revisão de 12/11/2026).

**Ator:** backend do Gabarito360; aplicativo financeiro
**Pré-condições:** o aplicativo financeiro está indisponível; existe concessão válida, dada antes por ele.
**Pós-condições:** o consumo em contingência é reconciliado, sem duplicidade.

**Fluxo principal:**
1. O backend autoriza a submissão só dentro da concessão válida.
2. O backend guarda o mínimo de integração para reconciliar. Esse estado não é carteira.
3. Na reconexão, o aplicativo financeiro confirma o consumido e libera o restante.

**Fluxos alternativos e de erro:**
- 1a. Sem concessão válida: o envio é recusado (UC-081-02, 2b).
- 3a. Divergência excepcional: o aplicativo financeiro abre a exceção de reconciliação para a via administrativa.

**Regras de negócio:** [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §6; [contrato](../../arquitetura/14-integracao-financeira.md) §10.

**Critérios de aceite:**
- [ ] A reconciliação não duplica consumo (teste B).
- [ ] O aparelho não persiste dados financeiros e não guarda concessão de contingência nem autorização.
- [ ] Sem concessão válida, nada é autorizado.
- [ ] A queda da API financeira nunca ativa o simulador.
- [ ] A carteira nunca fica negativa por falha técnica.

**Situação:** Não implementado.

---

## UC-081-09 — Ver saldo e extrato

**Ator:** responsável pelo contexto de cobrança e quem consome (D-2); Financeiro (só totais)
**Pré-condições:** o contexto tem saldo ou histórico.
**Pós-condições:** nenhuma.

**Fluxo principal:**
1. O usuário abre a página de créditos.
2. O Gabarito360 consulta o aplicativo financeiro e mostra o saldo, a franquia do ciclo, o limite do professor (quando houver) e o extrato recebidos.
3. O extrato mostra aluno e aplicação apenas para quem tem acesso à prova.

**Critérios de aceite:**
- [ ] O saldo exibido é o recebido do aplicativo financeiro. O Gabarito360 não calcula saldo.
- [ ] O Financeiro não vê nomes de alunos.
- [ ] Os nomes de alunos não vão ao aplicativo financeiro; o Gabarito360 junta aluno e aplicação ao extrato só para quem tem acesso à prova.
- [ ] Sem resposta do aplicativo financeiro, a tela mostra erro, não saldo antigo como atual.

**Situação:** Não implementado.

---

## UC-081-10 — Conceder crédito por contribuição validada

**Ator:** curadoria do acervo (FN-044); aplicativo financeiro
**Permissão:** chave de curadoria a definir
**Escopo:** carteira pessoal de quem contribuiu
**Pré-condições:** a contribuição passou pela validação ou curadoria e foi aprovada.
**Pós-condições:** o aplicativo financeiro registra um lote de origem `REWARD` na carteira pessoal do professor.

**Fluxo principal:**
1. O professor contribui com uma questão.
2. A curadoria aprova a contribuição.
3. O Gabarito360 informa a contribuição validada ao aplicativo financeiro.
4. A regra do aplicativo financeiro calcula a quantidade e registra a concessão (`GRANT`, origem `REWARD`) na carteira pessoal de quem contribuiu.

**Fluxos alternativos e de erro:**
- 2a. A contribuição não é aprovada: nenhum crédito é concedido.

**Regras de negócio:** planejamento §6.6.

**Critérios de aceite:**
- [ ] Enviar uma contribuição não gera crédito.
- [ ] A recompensa não altera a autoria contextual.
- [ ] O crédito vai à carteira pessoal, mesmo quando a contribuição nasceu em contexto institucional.
- [ ] O crédito de recompensa não pode ser transferido para a escola.
- [ ] O aluno não participa do programa.
- [ ] A concessão registra quem concedeu, por quê e quando.
- [ ] ⚠ A titularidade e a licença das questões dependem da pendência jurídica 2, na [FN-068 §8](../068-financeiro/planejamento.md).

**Situação:** Não implementado.

---

## UC-081-11 — Consumir dentro do limite mensal do professor

**Ator:** professor que corrige no modo servidor com o saldo da instituição; responsável financeiro institucional (define o limite)
**Permissão:** `cartao.enviar`; o limite e a permissão são conferidos pelo aplicativo financeiro
**Escopo:** contexto institucional (escola ou núcleo) da operação
**Pré-condições:** o responsável financeiro institucional definiu um limite mensal para o professor no aplicativo financeiro.
**Pós-condições:** o consumo conta no limite do professor e no saldo da instituição; nenhum crédito vai à carteira pessoal.
**Tela/rota:** Leitor360, modal do modo servidor; aviso de limite (mockup na subfase 0.1)

**Fluxo principal:**
1. O professor envia uma submissão no contexto da instituição.
2. O aplicativo financeiro confere o saldo da instituição e o limite mensal do professor.
3. Dentro do limite, o consumo segue o UC-081-01.

**Fluxos alternativos e de erro:**
- 2a. Limite atingido: o envio é recusado, mesmo com saldo na instituição. O professor vê o aviso do limite. Ele não vê o saldo da instituição sem permissão própria.
- 2b. Saldo da instituição esgotado: UC-081-02.

**Regras de negócio:** planejamento §6.2, item 10a, e §8 (D-2). [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §5.

**Critérios de aceite:**
- [ ] O limite atingido recusa o envio (teste G).
- [ ] O limite não transfere crédito para a carteira pessoal do professor.
- [ ] Renovar o limite no mês seguinte não cria créditos para a instituição.
- [ ] O Gabarito360 não guarda nem calcula o limite.
- [ ] ⚠ Quem define o limite, o padrão sem limite e o fim do ciclo dependem dos detalhes pendentes da D-2.

**Situação:** Não implementado.

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-11 | Casos de uso preliminares, com as regras decididas no Leitor360 | lucasgenial |
| 2 | 2026-11-11 | Reescritos com as decisões de billing de 11/11/2026: identidade por `correction_subject_id`, reenvio sem custo, `NOT_DELIVERED`, alocação de franquia, contingência e créditos por contribuição validada | lucasgenial |
| 3 | 2026-11-12 | Reorientação para o aplicativo financeiro externo: saldo, reserva, compra, franquia, alocação e concessão passam a ser executadas por ele; UC-081-08 com protocolo pendente; UC-081-11 (limite mensal do professor) acrescentado | lucasgenial |
