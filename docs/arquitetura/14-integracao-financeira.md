# Integração com o aplicativo financeiro

**Atualizado em:** 12/11/2026, por lucasgenial

Este documento é o **dono do contrato de integração** entre o Gabarito360 e o aplicativo financeiro independente. Ele define capacidades, contextos, dados mínimos, consumo, idempotência, respostas, notificações e falhas. Os outros documentos citam este por link ([ADR-0055](../decisoes/0055-estrutura-da-documentacao-e-documento-dono.md)).

> ⚠ **Nada deste contrato está implementado.** O aplicativo financeiro ainda não existe. O código atual não tem a porta `BillingClient`, cliente da API nem simulador. Este documento descreve requisitos. Ele não descreve controles em operação.

A decisão que sustenta este contrato é o [ADR-0061](../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md).

## 0. Como ler as marcas

Cada regra tem uma marca de origem. Uma regra sem marca segue a marca do título da seção.

| Marca | Significado |
|---|---|
| **[Diretriz]** | Orientação expressa do responsável. Ela governa o contrato. |
| **[Histórico]** | Regra construída nas sessões de 11/11/2026. A definição continua válida; a implementação local foi superada. |
| **[Proposto]** | Requisito técnico proposto para concretizar a diretriz. Ele ainda não foi aprovado como decisão. |
| **[Pendente]** | Assunto aberto. Não feche por inferência. |

## 1. Fronteira de autoridade

**[Diretriz]** O aplicativo financeiro é independente. Ele tem código-fonte, banco, serviço, credenciais e implantação próprios. O Gabarito360 o acessa só por integração. O Gabarito360 não acessa o banco financeiro.

O nome de trabalho encontrado na documentação é M3 Core. A marca comercial definitiva não está escolhida.

| Assunto | Gabarito360 | Aplicativo financeiro |
|---|---|---|
| Provas, aplicações, cartões, submissões, alunos, correções e resultados | Dono | Não recebe |
| Permissões educacionais e contexto Núcleo/Escola/Professor | Dono | Recebe só a referência do contexto |
| Catálogo comercial, planos, assinaturas e preços | Não guarda | Dono |
| Pagamentos, meios de pagamento e reembolsos | Não guarda (exceto o legado, §11) | Dono |
| Créditos, bônus, franquias, recompensas, saldo e carteiras | Exibe o que recebe | Dono |
| Autorização de consumo, reservas, contabilização e reversões | Pede e registra o resultado | Dono |
| Limites institucionais de consumo por professor | Respeita a resposta | Dono |
| Auditoria financeira | Guarda só o registro operacional | Dono |

Uma tela do Gabarito360 pode mostrar saldo, limites ou histórico obtidos do aplicativo financeiro. Mostrar um dado não transfere a autoridade sobre ele.

Fora do escopo dos dois produtos, sem decisão específica: ERP escolar, folha de pagamento e cobrança de mensalidades de estudantes.

## 2. Componentes no Gabarito360

**[Diretriz]** O módulo financeiro do Gabarito360 é limitado. Ele dá acesso contextual às capacidades financeiras de que o produto precisa. Ele não é um sistema financeiro completo. Ele não é uma porta de administração do aplicativo externo.

| Componente | Função | Marca |
|---|---|---|
| Porta `BillingClient` | Interface única do Gabarito360 com o financeiro. O resto do código não conhece o aplicativo externo. | [Histórico] |
| Cliente da API | Implementação da porta que chama o aplicativo financeiro real. | [Diretriz] |
| Simulador de integração | Implementação da porta só para testes (§9). | [Proposto] |
| Registro operacional da correção | Identidade da submissão, correlação das chamadas e resultado da integração (§6). | [Histórico] |
| Referências externas | Identificadores opacos de conta e de operação no aplicativo financeiro. | [Histórico] |

Os nomes `authorize`, `reserve`, `commit`, `release` e `getBalance` vêm do histórico de 11/11/2026. Eles nomeiam as capacidades da porta. Eles não definem endpoints.

**É proibido no Gabarito360:**

- carteira, lotes de crédito, ledger ou saldo autoritativo;
- preço, franquia ou regra comercial fixos no código ou em tabela local;
- esquema financeiro local, inclusive tabelas com o prefixo `billing_` que guardem saldo ou movimentação;
- motor de cálculo de consumo, de ordem de fontes ou de validade de créditos;
- credencial administrativa do aplicativo financeiro ou segredos de gateway;
- conexão ao banco do aplicativo financeiro.

O registro operacional não é débito financeiro. Ele não é fonte de saldo.

## 3. Capacidades do contrato

**[Proposto]** A porta oferece as capacidades abaixo. Endpoints, formatos e protocolo são definidos depois, com o aplicativo financeiro.

| Capacidade | O que faz | Quem chama |
|---|---|---|
| Consultar disponibilidade | Informa se o contexto pode consumir o recurso, e o saldo e os limites que o usuário pode ver | Gabarito360, para um usuário no contexto |
| Autorizar e reservar | Retém capacidade para uma operação, com referência estável | Gabarito360, no envio ao modo servidor |
| Confirmar consumo | Converte a reserva em consumo definitivo | Gabarito360, no resultado utilizável |
| Liberar | Desfaz a reserva sem consumo | Gabarito360, na falha da submissão |
| Consultar histórico do contexto | Devolve o extrato que o usuário pode ver | Gabarito360, para um usuário no contexto |
| Informar contribuição validada | Comunica um fato do produto; o aplicativo financeiro decide a recompensa | Gabarito360, depois da curadoria |

**Fora da credencial técnica do Gabarito360:** emitir créditos livremente, mudar preço, ajustar saldo, fazer reembolso em dinheiro, administrar planos, ver outros produtos ou outras instituições. Essas ações são do aplicativo financeiro, com identidade humana e permissão próprias (§8).

## 4. Contextos de cobrança

**[Histórico]** Os termos do produto são Núcleo, Escola e Professor. O aplicativo financeiro pode usar abstrações internas, mas a integração preserva esses termos.

1. Cada operação tarifada tem um único contexto de cobrança: professor, escola ou núcleo.
2. Não existe recurso automático de um contexto a outro. O crédito pessoal não financia operação institucional.
3. A carteira pessoal e a carteira institucional ficam isoladas.
4. A hierarquia Núcleo/Escola/Professor não autoriza consumo implícito do saldo de outro contexto.
5. O núcleo delega franquia à escola só por alocação explícita. Delegação não é transferência de créditos.
6. Créditos não são sacáveis, negociáveis nem transferíveis entre pessoas, instituições ou produtos.

**[Diretriz]** O responsável financeiro institucional pode comprar créditos para a entidade. Ele também administra limites mensais de consumo por professor.

7. O limite por professor governa o consumo do saldo institucional. Ele não cria carteira pessoal e não transfere crédito ao professor.
8. Renovar um limite mensal não cria créditos novos para a instituição.
9. O aplicativo financeiro guarda e aplica o limite. O Gabarito360 só respeita a resposta.
10. **[Pendente]** Os detalhes das permissões e das quotas: quem é o responsável financeiro em cada contexto, quem pode alterar limites e o que acontece no fim do ciclo.

O Gabarito360 envia o contexto da operação. O aplicativo financeiro confere se o contexto, o usuário e o vínculo permitem a ação.

## 5. Dados mínimos

**[Proposto]** A integração envia o mínimo necessário para autorizar e contabilizar.

| Envia | Não envia |
|---|---|
| Referência opaca do produto, do recurso e do contexto | Fotos, imagens ou recortes |
| Referência opaca do usuário que pede a ação | Respostas, leituras ou notas |
| Chave estável da operação (§6) | Nome, matrícula ou dado pessoal do aluno |
| Quantidade | Conteúdo da prova ou gabarito |
| Referência de correlação da chamada | Dados pedagógicos completos |

Um identificador opaco ainda pode ser vinculado a uma pessoa. Trate-o como dado pessoal: mínimo, protegido e com retenção definida ([11 Segurança e privacidade](11-seguranca-e-privacidade.md)).

## 6. Consumo e idempotência

As regras funcionais da correção são da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md). Este item diz o que o contrato exige delas.

**[Histórico]**

1. A unidade da correção no servidor é uma submissão: um aluno em uma aplicação, com todas as páginas.
2. A identidade é `correction_subject_id`. A chave da operação é `correction:{correction_subject_id}`. Nunca use só o id da prova.
3. Uma operação pode ter várias tentativas e reservas. Ela tem no máximo um consumo definitivo.
4. Reenvio, foto nova e recálculo por alteração de gabarito usam a mesma chave. Eles não criam consumo novo.
5. Uma ocorrência avaliativa nova (recuperação, segunda chamada, aplicação nova) tem identidade própria.
6. O consumo é confirmado quando o resultado utilizável fica persistido em `READY_FOR_REVIEW`.
7. O desenho histórico separava três rastros: um evento lógico de consumo por operação; movimentos da carteira, quando ela é a fonte; e consumo auditável da franquia. Esses rastros são requisitos do aplicativo financeiro. Eles não são tabelas do Gabarito360.

**[Proposto]**

8. Toda chamada que muda estado leva uma referência estável. Repetir a chamada com a mesma referência não repete o efeito.
9. A mesma chave com dados diferentes é recusada como conflito.
10. O Gabarito360 guarda a chave e o último resultado conhecido de cada operação, para repetir com segurança.
11. Uma reserva sem confirmação expira no aplicativo financeiro. O prazo é configuração operacional, não regra deste contrato.

## 7. Respostas e notificações

**[Proposto]** Toda resposta cai numa destas categorias. Os códigos e os formatos são definidos com o aplicativo financeiro.

| Categoria | Efeito no Gabarito360 |
|---|---|
| Autorizado | O envio ao modo servidor segue |
| Recusado por falta de franquia e de crédito | O envio é recusado antes da fila; a tela mostra os caminhos permitidos |
| Recusado por limite do professor | O envio é recusado; a tela informa o limite, sem expor o saldo da instituição a quem não pode ver |
| Recusado por contexto ou permissão | O envio é recusado; nada é cobrado |
| Repetição reconhecida | O Gabarito360 usa o resultado anterior |
| Conflito de chave | O Gabarito360 registra o erro e não cobra de novo |
| Indisponível ou sem resposta | Ver §10 |

As notificações do aplicativo financeiro ao Gabarito360 servem só para manter a tela e o registro operacional em dia. Exemplos: reserva expirada, resultado de reconciliação, mudança de saldo ou de limite. Cada notificação é autenticada, correlacionada pela referência da operação e idempotente. **[Pendente]** O mecanismo (chamada de retorno, fila ou consulta periódica).

## 8. Fronteira de confiança e credenciais

**[Diretriz]**

1. O aplicativo financeiro autentica o Gabarito360 como aplicação chamadora. Ele autoriza cada ação no contexto permitido.
2. A credencial técnica do Gabarito360 não administra o financeiro. Ela alcança só as capacidades do §3.
3. O Gabarito360 não declara sozinho que um usuário é administrador ou que um saldo existe. O aplicativo financeiro confere com os próprios controles.
4. Ações humanas financeiras (comprar créditos, definir limites, consultar faturas) exigem identidade, vínculo e permissão próprios, validados pelo aplicativo financeiro.
5. Os produtos não compartilham banco, usuário administrativo de banco nem segredos de gateway.

**[Proposto]**

6. A credencial técnica tem escopo por produto e por ambiente. Homologação e produção usam credenciais diferentes.
7. A credencial pode ser revogada e trocada sem parar o produto.
8. O aplicativo financeiro limita o volume de chamadas por credencial e registra toda chamada.
9. Testes de isolamento provam que a credencial do Gabarito360 não executa ações administrativas nem vê outro produto ou outra instituição.

**[Pendente]** O lugar das ações humanas: painel do aplicativo financeiro, navegação integrada ou tela limitada no Gabarito360. Nenhuma opção autoriza guardar credencial financeira global no Gabarito360. O provedor de identidade também está em aberto.

A separação reduz o alcance de uma credencial comprometida. Ela não elimina o risco: a credencial ainda pode ser usada dentro das ações autorizadas. Por isso, o desenho exige limites, revogação, rastreabilidade e testes de isolamento. Referências de apoio: OWASP Microservices Security Cheat Sheet, OWASP Authorization Cheat Sheet e OWASP API5:2023 (Broken Function Level Authorization).

## 9. Simulador de integração

**[Proposto]** Antes de o aplicativo financeiro existir, os testes do Gabarito360 usam um simulador da porta `BillingClient`.

1. O simulador não tem carteira, ledger nem débito real. Ele só devolve as respostas do §7.
2. Ele cobre os cenários: autorizado, recusado sem saldo, limite do professor atingido, contexto sem permissão, repetição, conflito de chave e indisponibilidade.
3. O uso é explícito, por configuração do ambiente de teste. Ele fica isolado de cobrança real.
4. O simulador não desliga nem amplia permissões educacionais. `cartao.enviar` e as demais chaves continuam valendo.
5. Um consumo feito no simulador nunca vira cobrança nem dívida retroativa.
6. **[Diretriz]** A indisponibilidade da API real nunca ativa o simulador. Ela também não ativa um modo "autoriza tudo".
7. O simulador não é o antigo `LocalBillingAdapter`. Aquele adaptador guardava carteira e ledger no banco do Gabarito360 e foi superado (ADR-0061).

## 10. Falhas e disponibilidade

**[Proposto]**

| Funciona sem o aplicativo financeiro | Depende da resposta financeira |
|---|---|
| Login, cadastros, provas, turmas, alunos e resultados | Envio de submissão ao modo servidor |
| Leitura no aparelho, que nunca consome crédito | Exibição atualizada de saldo e de limites |
| Revisão, conferência e fechamento da correção | Compra de créditos e gestão de limites |

1. Sem autorização válida, o envio ao modo servidor é recusado. O usuário continua com a leitura no aparelho e a revisão manual.
2. A chamada tem espera limitada. Na falha, o Gabarito360 repete com a mesma referência (§6, item 8). Ele não repete sem limite.
3. Uma confirmação de consumo que não chega fica pendente no registro operacional. O Gabarito360 a repete, com a mesma referência, até ter resposta. **[Pendente]** O que acontece com o resultado da correção enquanto a confirmação está pendente.
4. O Gabarito360 evita chamadas desnecessárias: ele não consulta saldo a cada tela quando o dado não é usado.
5. Os tempos, os limites de repetição e as tolerâncias não estão aprovados. Eles não são fixados aqui.

**Contingência.** **[Histórico]** O desenho de 11/11/2026 usava capacidade concedida antes pelo serviço (lease) e reconciliação posterior, sem saldo negativo. **[Diretriz]** Não existe autorização sem serviço disponível ou sem concessão válida. **[Pendente]** O protocolo de concessão, o lugar do estado mínimo e a reconciliação, na fronteira entre os dois aplicativos. Estado mínimo de integração no backend não é carteira.

**Desempenho.** Separar o financeiro desacopla a carga e o domínio. Isso não garante, por si só, desempenho melhor. A integração é medida e testada como as outras rotas ([12 Desempenho](12-desempenho.md), [ADR-0052](../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)).

## 11. Legado de pagamentos

O código atual tem o legado do MP-027: tabelas `planos`, `assinaturas` e `pagamentos`, rotas `assinaturas/*` e o webhook `POST /webhooks/mercadopago`. A situação dele é da [FN-068](../funcionalidades/068-financeiro/planejamento.md).

**[Diretriz]** Esta documentação não remove nem desliga o legado. Desativar checkout, assinaturas, webhook, dados ou tabelas exige investigação e autorização em tarefa própria.

## 12. Pendências deste contrato

| Pendência | Onde é tratada |
|---|---|
| Protocolo, endpoints, formatos e mecanismo de notificação | Projeto do aplicativo financeiro, com revisão deste documento |
| Provedor de identidade e lugar das ações humanas | §8; decisão do responsável |
| Detalhes de permissões e quotas do limite por professor | §4; [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Protocolo de contingência | §10; decisão do responsável |
| Entidade de aplicação e formato do `correction_subject_id` | [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Quantidades por plano (D-3) e formato da compra avulsa (D-4) | [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Pendências jurídicas 1 (compra, arrependimento e reembolso) e 2 (autoria e licença das questões) | [FN-068 §8](../funcionalidades/068-financeiro/planejamento.md) |
| Infraestrutura, marca definitiva e meios de pagamento do aplicativo financeiro | Fora deste repositório |
