# 081 — Créditos de correção — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Conforme princípios:** versão 14
**Posição no plano mestre:** fora das ondas (candidata; próxima documentação, aguarda priorização do responsável)
**Natureza:** nova
**Origem:** surgiu no planejamento do Leitor360 (FN-079) e do Escriba (FN-080) em 2026-11-11, com o modo servidor
**Atualizado em:** 2026-11-11, por lucasgenial

Planejamento preliminar. Ele reúne o que já está decidido e as perguntas da próxima sessão de documentação. Nada é executado antes da priorização e da aprovação destes documentos.

Neste documento, "(proposta)" significa: proposta do planejamento, a confirmar pelo responsável. "⚠" marca o que ainda não foi confirmado.

## 1. Objetivo

O processamento no aparelho do usuário não custa nada ao Gabarito360. O processamento no servidor custa. Esta funcionalidade controla esse custo com créditos de correção. Ela guarda o saldo, consome um crédito a cada correção feita no servidor, recusa o envio sem saldo e mostra o extrato. Ela também liga os créditos aos planos e à compra avulsa.

## 2. Escopo

- **Inclui:**
  - Saldo de créditos de correção por titular (quem é o titular: D-1 deste documento).
  - Consumo de 1 crédito por correção de prova de um aluno no servidor (aluno × prova), qualquer que seja o número de páginas.
  - Reserva e confirmação do consumo, com idempotência (o mesmo aluno × prova não cobra duas vezes).
  - Reenvio sem custo dos alunos que o servidor marcou com dificuldade.
  - Recusa do envio sem saldo, com mensagem.
  - Aviso prévio de consumo no Leitor360 (o texto já está decidido na FN-079).
  - Extrato (entradas e consumos) e trilha de auditoria.
  - Limite incluso no plano e crédito avulso acima do limite (D-3, D-4).
  - Alertas de saldo baixo (proposta).
- **Não inclui:**
  - Contratos, faturas e pagamentos → [FN-068](../068-financeiro/planejamento.md). Esta FN pede a cobrança da compra avulsa à FN-068.
  - O modo servidor, a fila e o marcador de identificação → [FN-079](../079-leitor360/planejamento.md) §6.12.
  - O Escriba no servidor → [FN-080](../080-ia-local-transcricao/planejamento.md) §8.3.
  - Aviso ao terminar a fila → [FN-057](../057-notificacoes/planejamento.md).
  - Programa de recompensas para professores (crédito por questão aprovada, após curadoria): ⚠ fica fora até o responsável decidir se usa a mesma moeda (D-8).

## 3. Fontes de verdade

- Decisões do responsável já registradas: [FN-079 planejamento §6.12 e §8](../079-leitor360/planejamento.md), [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), [FN-080 §8.3](../080-ia-local-transcricao/planejamento.md).
- Telas: mockup aprovado `mockups/leitor360.html`, tela H (`data-tela="sem-ia"`) e modal "Usar a correção no servidor".
- Financeiro: [FN-068](../068-financeiro/planejamento.md) (planos, assinaturas e pagamentos que já existem no código).
- Perfis e permissões: [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| FN-068 financeiro | Compra avulsa de créditos gera cobrança e pagamento; planos definem o limite incluso | Em planejamento |
| FN-004 auditoria | Trilha de entradas e consumos | Parcial |
| FN-057 notificacoes | Alerta de saldo baixo (proposta) | Em planejamento |

**Funcionalidades que dependem desta:** FN-079 (subfase 4.3, modo servidor das objetivas) e FN-080 / FN-079 6.3 (Escriba no servidor). Elas não publicam o modo servidor sem esta FN.

## 5. Fases e subfases (preliminares)

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Planejamento, casos de uso e mockups aprovados, com as decisões D-1 a D-8 | O responsável aprova os documentos e o mockup das telas de saldo, extrato e compra | Pendente | |
| 1.1 | Modelo de dados: saldo, lançamentos (entrada, reserva, consumo, estorno) e idempotência por aluno × prova | Testes provam: 1 crédito por aluno × prova; reenvio marcado não cobra; nenhum saldo negativo; concorrência no MariaDB sem consumo duplo | Pendente | |
| 1.2 | Consumo integrado ao modo servidor (FN-079 4.3) | Sem saldo, o envio é recusado antes de entrar na fila; o consumo só é confirmado quando a correção termina | Pendente | |
| 2.1 | Telas de saldo e extrato | Conforme mockup aprovado; responsivo (ADR-0057) | Pendente | |
| 3.1 | Limite incluso no plano e compra avulsa (com FN-068) | O limite renova no período do plano; a compra avulsa só credita depois do pagamento confirmado | Pendente | |

## 6. Contrato técnico (diretrizes)

1. **Unidade (decidida):** 1 crédito = 1 correção de prova de 1 aluno no servidor (aluno × prova). Várias páginas do mesmo aluno na mesma prova contam 1 crédito.
2. **Só no servidor (decidida):** o processamento no aparelho nunca consome.
3. **Sem saldo (decidida):** o envio é recusado.
4. **Reenvio sem custo (decidida):** quando o servidor marca dificuldade num aluno, o reenvio desse aluno não cobra de novo. Um reenvio de aluno sem essa marca cobra.
5. **Qualidade (decidida):** problema de qualidade da imagem vai para a revisão manual do usuário. ⚠ Se a correção recusada pela barreira cobra ou não, ainda não foi decidido (D-6).
6. **Reserva e confirmação (proposta):** ao entrar na fila, o sistema reserva 1 crédito por aluno × prova. Ao terminar, confirma. Em falha do servidor, estorna.
7. **Lançamentos imutáveis (proposta):** o saldo é a soma de lançamentos append-only (entrada, reserva, consumo, estorno), com autor, vínculo, prova, aluno e `envio_id`. Nenhum lançamento é apagado.
8. **Valores em inteiros (proposta):** créditos em unidades inteiras; preço em centavos, como na FN-068.
9. **Dados de aluno:** o extrato mostra aluno e prova só para quem tem acesso à prova. O perfil Financeiro vê só totais (regra da FN-068: o Financeiro não acessa dados de alunos).

## 7. Interface

Telas previstas (mockup na subfase 0.1, sobre a casca e o CSS reais, ADR-0056 e ADR-0057): saldo no Leitor360 (já no modal do modo servidor), página de saldo e extrato, compra avulsa e alerta de saldo baixo. A conferência é registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável

Perguntas para a próxima sessão de documentação.

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| D-1 Titular do saldo | (a) Professor; (b) escola; (c) organização (núcleo ou secretaria); (d) conforme o eixo da assinatura (individual, escola ou organização) | (d): o saldo pertence a quem assina | Pendente |
| D-2 Quem consome do saldo da escola ou da organização | (a) Todo professor do escopo; (b) só quem o responsável autoriza; (c) cota por professor | (a) com permissão própria, e (c) como evolução | Pendente |
| D-3 Limite incluso no plano | Quantidade por plano e período de renovação (mensal, anual) | A definir com os preços da FN-068 | Pendente |
| D-4 Crédito avulso | Pacotes fixos ou quantidade livre; preço por crédito | Pacotes fixos | Pendente |
| D-5 Validade | (a) Não vence; (b) o incluso vence no fim do período e o avulso não vence; (c) tudo vence | (b) | Pendente |
| D-6 Correção recusada pela barreira de qualidade | (a) Não cobra; (b) cobra, porque o servidor processou | (a): vai para revisão manual sem cobrança | Pendente |
| D-7 Transferência e compartilhamento | (a) Proibidos; (b) permitidos dentro da organização | ⚠ As instruções do projeto dizem "créditos não são compartilhados nem transferidos" (regra do programa de recompensas). Confirmar se vale também para os créditos de correção | Pendente |
| D-8 Recompensas (crédito por questão aprovada após curadoria) | (a) Mesma moeda dos créditos de correção; (b) moeda separada | A decidir | Pendente |
| D-9 Estorno e reembolso | Quando o crédito volta (falha do servidor, correção cancelada) | Estorno automático em falha do servidor; sem reembolso em dinheiro (alinha com a FN-068: sem devolução) | Pendente |

| Risco | Efeito | Mitigação |
|---|---|---|
| Consumo duplo em concorrência | Dois envios do mesmo aluno × prova cobram duas vezes | Chave única aluno × prova × correção; teste de concorrência no MariaDB |
| Saldo zerado no meio de um lote | Parte do lote fica sem correção | Reserva antes de entrar na fila; o lote mostra quantos créditos vai usar antes de enviar (proposta) |
| Custo real maior que o preço | Fila longa e GPU (FN-080 §8.3) | Medir o custo por correção na subfase 6.3 da FN-079 antes de fixar preço |

## 9. Ramos desta entrega

Nenhum ainda.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-11 | Criado como candidata, com as regras já decididas no Leitor360 e as perguntas da próxima sessão | lucasgenial |
