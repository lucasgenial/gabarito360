# Integração com o aplicativo financeiro

**Atualizado em:** 12/11/2026, por lucasgenial

Este documento é o **dono do contrato de integração** entre o Gabarito360 e o aplicativo financeiro independente. Ele define:

- capacidades, contextos e dados mínimos;
- consumo, idempotência, respostas e notificações;
- falhas e a travessia para a Central do cliente.

Os outros documentos citam este por link ([ADR-0055](../decisoes/0055-estrutura-da-documentacao-e-documento-dono.md)).

> ⚠ **Nada deste contrato está implementado.** O aplicativo financeiro e a Central do cliente ainda não existem. O código atual não tem a porta `BillingClient`, cliente da API, simulador, consulta de Plano e uso nem travessia. Este documento descreve requisitos. Ele não descreve controles em operação. Nenhuma rota, tabela ou tecnologia é escolhida aqui.

Duas decisões sustentam este contrato: o [ADR-0061](../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) e o [ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md). O ADR-0062 substitui o ADR-0061 em parte. Ele troca a finalidade do "módulo financeiro limitado" por Plano e uso. Ele leva a governança institucional para a Central do cliente. As outras regras do ADR-0061 continuam.

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

O nome de trabalho encontrado na documentação é M3 Core. Ele não é marca. A marca comercial definitiva não está escolhida.

**[Diretriz]** ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §1) O mesmo aplicativo financeiro tem duas experiências humanas, com permissões próprias:

- a **Central do cliente**, para o professor autônomo e o representante institucional;
- a **administração financeira da empresa**.

Isso não exige três aplicativos. Isso não escolhe stack nem marca.

| Assunto | Gabarito360 | Aplicativo financeiro |
|---|---|---|
| Provas, aplicações, cartões, submissões, alunos, correções e resultados | Dono | Não recebe |
| Permissões educacionais e contexto Núcleo/Escola/Professor | Dono | Recebe só a referência do contexto |
| Pessoa, conta de acesso, perfil e vínculo | Dono | Recebe só referências opacas |
| Conta contratante e titularidade contratual | Não guarda. Consulta pelo vínculo | Dono |
| Catálogo comercial, planos, assinaturas e preços | Não guarda | Dono |
| Contratação, ofertas, mudança de plano, adicionais e capacidade institucional | Oferece só a entrada contextual (§13) | Dono, na Central do cliente |
| Administração financeira da empresa, inclusive a visão de vários clientes | Não mostra | Dono |
| Pagamentos, meios de pagamento e reembolsos | Não guarda (exceto o legado, §11) | Dono |
| Créditos, bônus, franquias, recompensas, saldo e carteiras | Exibe o que recebe, em Plano e uso | Dono |
| Autorização de consumo, reservas, contabilização e reversões | Pede e registra o resultado | Dono |
| Referências de acompanhamento e limites institucionais | Respeita a resposta. Não edita | Dono |
| Auditoria financeira | Guarda só o registro operacional | Dono |

Uma tela do Gabarito360 pode mostrar cobertura, disponibilidade, limites, consumo ou extrato obtidos do aplicativo financeiro. Mostrar um dado não transfere a autoridade sobre ele.

**Conta contratante. [Diretriz]** (condição B1) A titularidade contratual é da conta contratante, no aplicativo financeiro. O contrato pode ser da conta contratante da escola. Cada professor consulta pelo seu vínculo. O extrato por vínculo não muda a titularidade.

As definições de pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação estão no [04 Identidade, acesso e permissões](04-identidade-acesso-e-permissoes.md) e no [Glossário](../glossario.md).

**Limitação do legado.** O legado do MP-027 usa a conta de acesso como titular: `assinaturas.titular_id` referencia `usuarios` (`apps/api/database/migrations/2026_06_19_000002_create_assinaturas_table.php:14`). As consultas filtram por `titular_id` igual ao usuário autenticado (`apps/api/app/Http/Controllers/Api/AssinaturaController.php:26` e `:43`). O login calcula `eh_titular` pelo mesmo critério (`apps/api/app/Http/Controllers/Api/AuthController.php:47`). Isso é limitação do legado, não regra do modelo novo.

Fora do escopo dos dois produtos, sem decisão específica: ERP escolar, folha de pagamento e cobrança de mensalidades de estudantes.

## 2. Componentes no Gabarito360

**[Diretriz]** ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2) **Plano e uso** é a área do Gabarito360 que mostra, por vínculo, as informações autorizadas recebidas do aplicativo financeiro. Essas informações são:

- plano vigente ou cobertura institucional;
- recursos incluídos, ciclo, quantidades e percentuais aplicáveis;
- disponibilidade e operações em processamento;
- consumo (mês, ano e intervalo) e extrato.

Plano e uso oferece entradas contextuais para a Central do cliente. Ela não é um módulo de administração financeira. Ela não é uma porta de administração do aplicativo financeiro.

Os estados da área, os indicadores e o extrato são da [FN-068](../funcionalidades/068-financeiro/planejamento.md). Este contrato define só o que a integração entrega.

| Componente | Função | Marca |
|---|---|---|
| Porta `BillingClient` | Interface única do Gabarito360 com o financeiro. O resto do código não conhece o aplicativo financeiro. | [Histórico] |
| Cliente da API | Implementação da porta que chama o aplicativo financeiro real. Ela implementa a mesma porta que o simulador e é validada contra este contrato antes da troca. | [Diretriz] |
| Simulador de integração | Implementação genérica da porta para desenvolvimento e homologação; primeira entrega da integração (§9). | [Diretriz] |
| Consulta de Plano e uso | Leitura, pela porta, das informações do §3 para o vínculo ativo. A API do Gabarito360 faz a chamada. A Web só consome a API por HTTP. | [Diretriz] (finalidade); [Proposto] (forma) |
| Intenção de travessia | Registro da saída para a Central do cliente, ligado ao contexto de origem (§13). | [Proposto]; mecanismo [Pendente] |
| Registro operacional da correção | Identidade da submissão, correlação das chamadas e resultado da integração (§6). | [Histórico] |
| Referências externas | Identificadores opacos de conta e de operação no aplicativo financeiro. | [Histórico] |

Os nomes `authorize`, `reserve`, `commit`, `release` e `getBalance` vêm do histórico de 11/11/2026. Eles nomeiam as capacidades da porta. Eles não definem endpoints.

**É proibido no Gabarito360 ([ADR-0061](../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)):**

- carteira, lotes de crédito, ledger ou saldo autoritativo (o estado fictício do simulador, §9.3, não é saldo autoritativo);
- preço, franquia ou regra comercial fixos no código ou em tabela local;
- esquema financeiro local, inclusive tabelas com o prefixo `billing_` que guardem saldo ou movimentação;
- motor de cálculo de consumo, de ordem de fontes ou de validade de créditos;
- credencial administrativa do aplicativo financeiro ou segredos de gateway;
- conexão ao banco do aplicativo financeiro.

**Também é proibido ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md)):** catálogo comercial, preço calculado ou saldo próprio; edição de limite, alocação, capacidade, saldo ou plano; informação da administração financeira da empresa; soma local de registros de vários clientes.

O registro operacional não é débito financeiro. Ele não é fonte de saldo. A intenção de travessia também não é compra nem fonte de saldo.

**No cliente (navegador ou aparelho). [Diretriz]** A tela pode exibir, de forma temporária, dados financeiros autorizados para o usuário. O cliente não persiste esses dados. Ele não mantém saldo autoritativo, carteira, concessão de contingência nem autorização financeira. Ele nunca recebe credenciais técnicas ou administrativas.

**Troca do simulador pela API real. [Diretriz]** O simulador e o cliente da API implementam a mesma porta. Antes da troca, o cliente da API passa pelos mesmos cenários do §9.2 e pelos testes de isolamento do §8. O resto do Gabarito360 não muda na troca. A identidade `correction_subject_id`, o reenvio sem cobrança nova e o isolamento dos contextos continuam iguais.

## 3. Capacidades do contrato

**[Proposto]** A porta oferece as capacidades abaixo. Endpoints, formatos e protocolo são definidos depois, com o aplicativo financeiro.

### 3.1 Consumo

| Capacidade | O que faz | Quem chama |
|---|---|---|
| Consultar disponibilidade | Informa se o contexto pode consumir o recurso | Gabarito360, pelo vínculo ativo |
| Autorizar e reservar | Retém capacidade para uma operação, com referência estável | Gabarito360, no envio ao modo servidor |
| Confirmar consumo | Converte a reserva em consumo definitivo | Gabarito360, no resultado utilizável |
| Liberar | Desfaz a reserva sem consumo | Gabarito360, na falha da submissão |
| Informar contribuição validada | Comunica um fato do produto; o aplicativo financeiro decide a recompensa | Gabarito360, depois da curadoria |

As regras de consumo da correção são da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md). Este contrato não as copia.

### 3.2 Consultas de Plano e uso

| Capacidade | O que devolve |
|---|---|
| Consultar cobertura do vínculo | Plano vigente ou cobertura institucional; modalidade efetiva (§4); recursos incluídos; ciclo vigente; quantidades e percentuais aplicáveis. "Sem cobertura" é uma resposta válida, diferente de "indisponível" |
| Consultar disponibilidade por recurso | Para cada recurso: a unidade, a capacidade disponível, as operações em processamento e, quando houver, o teto de uso do período. No uso livre, a referência de acompanhamento, sem saldo artificial |
| Consultar consumo agregado | Consumo por recurso no mês, no ano e num intervalo. O universo e o período vêm do aplicativo financeiro |
| Consultar extrato | Linhas do extrato do vínculo, com tipo, situação, unidade e referência opaca da operação. As regras de exibição do extrato são da [FN-068](../funcionalidades/068-financeiro/planejamento.md) |
| Consultar contratações iniciadas | Estado de cada intenção de travessia (§13): pendente de confirmação, confirmada, cancelada, falha ou solicitação de ajuste em análise |

Toda consulta tem estas propriedades **[Proposto]**:

1. **Por vínculo, resolvido no servidor.** A API do Gabarito360 resolve o contexto de cobrança a partir do vínculo ativo da sessão. O middleware `contexto_vinculo` reconstrói esse vínculo a partir do token (`apps/api/bootstrap/app.php:22`). Um ID enviado em header, query ou payload nunca seleciona o contexto ([04](04-identidade-acesso-e-permissoes.md) §4.2, item 4). Nenhum identificador vindo do navegador escolhe vínculo, contexto ou conta contratante.
2. **Unidade por recurso, sem soma.** Cada recurso tem a própria unidade. Unidades de recursos diferentes nunca são somadas. Franquia, adicional, limite do professor e operações em processamento ficam em campos separados.
3. **Limite não é saldo.** O limite aparece como teto de uso do período. Ele não é saldo nem carteira.
4. **Percentual só onde se aplica.** O percentual não se aplica à referência de uso livre ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3). O aplicativo financeiro informa se cada quantidade admite percentual. O Gabarito360 não deduz isso.
5. **Hora da atualização.** Toda resposta traz o momento em que o aplicativo financeiro produziu o dado. A tela mostra esse momento.
6. **Sem dado velho como atual.** Na falha, a tela mostra o estado de indisponibilidade. Ela não mostra o último valor como se fosse atual (§10).
7. **Ordem e paginação estáveis** no extrato. **[Pendente]** A paginação e o intervalo máximo do extrato, no contrato externo.
8. **Entradas contextuais.** As entradas para a Central do cliente que o usuário pode ver dependem da resposta do aplicativo financeiro, não do perfil pedagógico (condição B2). O aplicativo financeiro confere o papel de novo em cada ação.

**Cache das consultas. [Pendente]** Há duas opções:

- (a) sem cache no início;
- (b) cache só na API, com chave pessoa + vínculo + contexto de cobrança, prazo curto e invalidação por notificação (§7).

O cliente nunca guarda cache (§2). A decisão fica para o planejamento de implementação, com medição de 1.000 usuários simultâneos ([12 Desempenho](12-desempenho.md); [ADR-0052](../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)).

**Fora da credencial técnica do Gabarito360:** emitir créditos livremente, mudar preço, ajustar saldo, alterar limite, alocação, capacidade ou plano, fazer reembolso em dinheiro, administrar planos, ver outros produtos ou outras instituições. Essas ações são do aplicativo financeiro, com identidade humana e permissão próprias (§8).

## 4. Contextos de cobrança e modalidades

**[Histórico]** Os termos do produto são Núcleo, Escola e Professor. O aplicativo financeiro pode usar abstrações internas, mas a integração preserva esses termos.

1. Cada operação tarifada tem um único contexto de cobrança: professor, escola ou núcleo.
2. Não existe recurso automático de um contexto a outro. O crédito pessoal não financia operação institucional.
3. A carteira pessoal e a carteira institucional ficam isoladas.
4. A hierarquia Núcleo/Escola/Professor não autoriza consumo implícito do saldo de outro contexto.
5. O núcleo delega franquia à escola só por alocação explícita. Delegação não é transferência de créditos.
6. Créditos não são sacáveis, negociáveis nem transferíveis entre pessoas, instituições ou produtos.

**Modalidades (itens 7 a 10)**

**[Diretriz]** ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §3) A modalidade vem do contrato, nunca da natureza (pública ou privada) da escola. Referência de acompanhamento, limite bloqueante e cobertura contratual são conceitos diferentes. A regra antiga do ADR-0061 ("o responsável financeiro institucional compra créditos e administra limites mensais") mudou de lugar e de forma: a governança acontece na Central do cliente e depende da modalidade. Os itens 7 a 10 abaixo continuam a numeração desta seção e têm esta marca.

7. **Professor autônomo.** Plano pessoal. Ele gere a própria contratação na Central do cliente.
8. **Instituição com uso livre monitorado.** Há medição. A instituição acompanha o uso e pede ajustes. Só o Financeiro do aplicativo financeiro ajusta, depois de conferir o contrato. A escola não redistribui. O Gabarito360 não cria bloqueio nem indicador de esgotamento por inferência. Nada sugere dívida por ultrapassar a referência. **[Pendente]** O comportamento ao ultrapassar a referência.
9. **Instituição com capacidade limitada.** O financeiro institucional autorizado administra a capacidade na Central do cliente, dentro do contratado e dos adicionais confirmados. Ele não cria capacidade acima do contratado. Consumo confirmado e reservas em processamento não são apagados. Do ADR-0061, continuam: o limite por professor governa o consumo do saldo institucional, sem criar carteira pessoal nem transferir crédito ao professor; e renovar um limite não cria créditos novos para a instituição.
10. **Em todas as modalidades.** O aplicativo financeiro guarda e aplica cobertura, referência e limite. O Gabarito360 só respeita a resposta. Nenhuma tela, rota ou formulário do Gabarito360 edita limite, alocação, capacidade, saldo ou plano.

**[Pendente]** As permissões detalhadas: quem é o financeiro institucional autorizado e o representante institucional em cada contexto; quem vê o resumo institucional; o modelo de distribuição, de reservas e de fim de ciclo; o professor sem limite. Nenhum desses papéis é deduzido de perfil pedagógico ou de direção (condição B2; [04](04-identidade-acesso-e-permissoes.md) §9.1).

O Gabarito360 envia o contexto da operação. O aplicativo financeiro confere se o contexto, o usuário e o vínculo permitem a ação.

## 5. Dados mínimos

**[Diretriz]** (condição B4, esclarecida pelo responsável em 12/11/2026) As referências opacas limitam os dados pedagógicos. Elas não proíbem os dados de cobertura e de consumo necessários.

**[Proposto]** A integração troca o mínimo necessário para autorizar, contabilizar e exibir Plano e uso.

| O Gabarito360 envia | O aplicativo financeiro devolve | Nunca circula, em nenhum sentido |
|---|---|---|
| Referência opaca do produto, do recurso e do contexto de cobrança | Cobertura, plano vigente, modalidade, recursos e ciclo | Fotos, imagens ou recortes |
| Referência opaca do usuário que pede a ação e do vínculo | Disponibilidade, consumo, unidade e situação de cada operação | Respostas, leituras ou notas |
| Chave estável da operação: `correction:{correction_subject_id}` (§6) | Referência opaca da operação em cada linha do extrato | Nome, matrícula ou dado pessoal do aluno |
| Quantidade | Hora da atualização | Conteúdo da prova ou gabarito |
| Referência de correlação da chamada | Estado das contratações iniciadas (§13) | Dados pedagógicos completos |

**Detalhe pedagógico de uma linha de consumo. [Diretriz]** O aplicativo financeiro não recebe nem devolve nome de aluno, resposta, nota ou outro detalhe pedagógico. A linha de consumo traz só a referência opaca da operação.

O Gabarito360 resolve aluno e aplicação no próprio banco, no momento do pedido. Ele usa o `Escopo` do vínculo e uma permissão **existente cuja proteção seja demonstrada**. Sem essa demonstração, ou na dúvida, o detalhe fica indisponível ("Dado restrito"). A linha de consumo continua visível.

A regra é da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md) §6.5, item 19. **[Pendente]** A chave exata, até a demonstração. Nenhuma chave é escolhida por "parecer restritiva".

**Dados da travessia (§13). [Proposto]** Na saída para a Central do cliente, o Gabarito360 envia só:

- referência opaca do vínculo e do contexto de cobrança;
- conta beneficiada, resolvida no servidor a partir do vínculo ativo, nunca a partir do navegador;
- tipo da intenção (por exemplo: comprar adicional, ver opções de plano, gerir a contratação, pedir ajuste, abrir a Central);
- recurso relacionado, quando houver;
- referência de retorno da tarefa de origem, escolhida da lista fechada do §13;
- chave única da intenção.

Ele nunca envia token de sessão do Gabarito360, credencial técnica, segredo de gateway nem dado pedagógico.

Um identificador opaco ainda pode ser vinculado a uma pessoa. Trate-o como dado pessoal: mínimo, protegido e com retenção definida ([11 Segurança e privacidade](11-seguranca-e-privacidade.md)). O aplicativo financeiro entra na lista de terceiros e no contrato de operador do documento 11 quando existir.

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

**[Proposto]** Toda resposta de consumo cai numa destas categorias. Os códigos e os formatos são definidos com o aplicativo financeiro.

| Categoria | Efeito no Gabarito360 |
|---|---|
| Autorizado | O envio ao modo servidor segue |
| Recusado por falta de franquia e de crédito | O envio é recusado antes da fila; a tela mostra os caminhos permitidos |
| Recusado por limite do professor | O envio é recusado; a tela informa o limite, sem expor o saldo da instituição a quem não pode ver |
| Recusado por falta de cobertura | O envio é recusado; a tela explica que o vínculo não cobre o recurso |
| Recusado por contexto ou permissão | O envio é recusado; nada é cobrado |
| Repetição reconhecida | O Gabarito360 usa o resultado anterior |
| Conflito de chave | O Gabarito360 registra o erro e não cobra de novo |
| Indisponível ou sem resposta | Ver §10 |

As consultas de Plano e uso (§3.2) distinguem pelo menos: sucesso, vazio (sem uso no período), sem cobertura, acesso negado e indisponível. A tela mapeia essas respostas para os estados da [FN-068](../funcionalidades/068-financeiro/planejamento.md). Nenhuma resposta do uso livre produz "limite atingido" por inferência.

**Notificações do aplicativo financeiro ao Gabarito360.** Elas servem só para manter a tela e o registro operacional em dia. Exemplos:

- reserva expirada e resultado de reconciliação;
- mudança de saldo, de cobertura, de referência de acompanhamento ou de limite;
- contratação confirmada, cancelada ou com falha;
- solicitação de ajuste recebida, em análise ou concluída.

Cada notificação é autenticada, correlacionada pela referência da operação ou pela chave da intenção (§13) e idempotente. Repetir a notificação não muda nada. Uma notificação nunca muda permissão educacional. **[Pendente]** O mecanismo (chamada de retorno, fila ou consulta periódica). **[Pendente]** A origem do evento de alteração de limite que aparece no extrato.

## 8. Fronteira de confiança e credenciais

**[Diretriz]**

1. O aplicativo financeiro autentica o Gabarito360 como aplicação chamadora. Ele autoriza cada ação no contexto permitido.
2. A credencial técnica do Gabarito360 não administra o financeiro. Ela alcança só as capacidades do §3.
3. O Gabarito360 não declara sozinho que um usuário é administrador, responsável financeiro ou representante institucional, nem que um saldo existe. O aplicativo financeiro confere com os próprios controles.
4. Ações humanas financeiras (comprar adicionais, mudar de plano, gerir a capacidade, consultar faturas) exigem identidade, vínculo e permissão próprios, validados pelo aplicativo financeiro.
5. Os produtos não compartilham banco, usuário administrativo de banco nem segredos de gateway.
6. Nenhum papel financeiro é deduzido de perfil pedagógico ou de direção. O código não tem chave de permissão financeira, e este contrato não cria nenhuma (condição B2; [04](04-identidade-acesso-e-permissoes.md) §9.1).
7. O perfil interno `financeiro` não tem tela em Plano e uso. A visão de vários clientes é da administração financeira da empresa, no aplicativo financeiro. A credencial do Gabarito360 não vê outras instituições, e o Gabarito360 não soma registros de vários clientes (condição B3). **[Pendente]** O papel futuro desse perfil.

**[Proposto]**

8. A credencial técnica tem escopo por produto e por ambiente. Homologação e produção usam credenciais diferentes.
9. A credencial pode ser revogada e trocada sem parar o produto.
10. O aplicativo financeiro limita o volume de chamadas por credencial e registra toda chamada.
11. Testes de isolamento provam que a credencial do Gabarito360 não executa ações administrativas nem vê outro produto ou outra instituição.

**Lugar das ações humanas. [Diretriz]** Decidido em 12/11/2026 ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §6): as ações humanas financeiras ficam na Central do cliente, no aplicativo financeiro. O Gabarito360 só oferece a entrada contextual (§13). Nenhuma opção autoriza guardar credencial financeira global no Gabarito360.

**[Pendente]** O provedor de identidade e o protocolo de identidade entre os produtos (§13.5).

A separação reduz o alcance de uma credencial comprometida. Ela não elimina o risco, porque a credencial ainda pode ser usada dentro das ações autorizadas. Por isso, o desenho exige limites, revogação, rastreabilidade e testes de isolamento. Referências de apoio: OWASP Microservices Security Cheat Sheet, OWASP Authorization Cheat Sheet e OWASP API5:2023 (Broken Function Level Authorization).

## 9. Simulador de integração (primeira entrega)

**[Diretriz]** A ausência do aplicativo financeiro não bloqueia os testes do Gabarito360. A primeira entrega da integração é um **simulador genérico da porta `BillingClient`**. Ele serve a qualquer recurso tarifado, não só à correção.

### 9.1 O que é real e o que é simulado

| Real (o produto roda de verdade) | Simulado (só a resposta financeira) |
|---|---|
| Login, vínculo e permissões educacionais | Autorização de consumo |
| Aplicação, submissão, envio e reenvio | Saldo, franquia e limites |
| Fila, processamento e leitura | Reserva |
| `READY_FOR_REVIEW`, conferência e fechamento da correção | Confirmação (`COMMIT`) e liberação (`RELEASE`) |
| Registro operacional da submissão (§6) | Extrato |

O simulador não simula a correção. O processamento educacional é o mesmo do modo real. Só a chamada à porta `BillingClient` recebe uma resposta simulada.

### 9.2 Cenários

O simulador aceita cenários configuráveis. Cada cenário produz a resposta do §7 correspondente.

1. Autorizado.
2. Sem saldo (sem franquia e sem crédito).
3. Limite da instituição atingido.
4. Limite do professor atingido.
5. Permissão ou contexto recusado.
6. Repetição idempotente (mesma chave, mesmo efeito).
7. Reserva, `COMMIT` e `RELEASE`, inclusive reserva que expira.
8. Tempo de resposta esgotado (timeout).
9. Indisponibilidade.

Os cenários atendem a testes automatizados, testes manuais e testes de ponta a ponta. A forma de escolher o cenário é definida na implementação (subfase 1.1 da [FN-068](../funcionalidades/068-financeiro/planejamento.md)).

### 9.3 Estado fictício

1. O simulador mantém saldo, extrato e limites fictícios. Eles mudam de forma coerente com as transições: a reserva retém, o `COMMIT` consome, o `RELEASE` devolve e a repetição não muda nada.
2. O estado do simulador fica separado das tabelas do produto. Ele pode ser reiniciado a qualquer momento.
3. Toda tela e todo registro que mostra esse estado o identifica como simulação.
4. O estado fictício não tem valor comercial. Ele nunca é migrado para o aplicativo financeiro.
5. O simulador não é carteira, ledger nem motor financeiro. Ele não aplica ordem de consumo, validade de lotes, preço nem regra comercial.
6. Os valores usados nos cenários são de teste. Eles não definem preços, planos nem cotas.

### 9.4 Ativação

1. O simulador só funciona com ativação explícita, por configuração no servidor.
2. Ambientes autorizados: desenvolvimento e homologação.
3. **[Pendente]** Piloto em produção com simulação. Ele não está habilitado e não deve ser habilitado sem decisão expressa do responsável.
4. A falha da API real nunca ativa o simulador. Ela também não ativa um modo "autoriza tudo".
5. Com o simulador ativo, não existe pagamento, dívida nem cobrança retroativa. Um consumo simulado nunca vira cobrança real.
6. O simulador não desliga nem amplia permissões educacionais. `cartao.enviar` e as demais chaves continuam valendo.
7. O simulador não é o antigo `LocalBillingAdapter`. Aquele adaptador guardava carteira e ledger no banco do Gabarito360 e foi superado ([ADR-0061](../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)).

## 10. Falhas e disponibilidade

**[Proposto]**

| Funciona sem o aplicativo financeiro | Depende da resposta financeira |
|---|---|
| Login, cadastros, provas, turmas, alunos e resultados | Envio de submissão ao modo servidor |
| Leitura no aparelho, que nunca consome crédito | Exibição atualizada de cobertura, disponibilidade, consumo e extrato |
| Revisão, conferência e fechamento da correção | Travessia para a Central do cliente e estado das contratações |

1. Sem autorização válida, o envio ao modo servidor é recusado. O usuário continua com a leitura no aparelho e a revisão manual.
2. A chamada tem espera limitada. Na falha, o Gabarito360 repete com a mesma referência (§6, item 8). Ele não repete sem limite.
3. Uma confirmação de consumo que não chega fica pendente no registro operacional. O Gabarito360 a repete, com a mesma referência, até ter resposta. **[Pendente]** O que acontece com o resultado da correção enquanto a confirmação está pendente.
4. O Gabarito360 evita chamadas desnecessárias: ele não consulta saldo a cada tela quando o dado não é usado.
5. Os tempos, os limites de repetição e as tolerâncias não estão aprovados. Eles não são fixados aqui.
6. Sem resposta, Plano e uso mostra o estado de indisponibilidade. Ela não mostra valor calculado no Gabarito360 nem o último valor como atual.

**Contingência.**

- **[Histórico]** O desenho de 11/11/2026 usava capacidade concedida antes pelo serviço (lease). Ele fazia a reconciliação depois e não permitia saldo negativo.
- **[Diretriz]** Não existe autorização sem serviço disponível ou sem concessão válida.
- **[Pendente]** O protocolo de concessão, o lugar do estado mínimo e a reconciliação, na fronteira entre os dois aplicativos.

Estado mínimo de integração no backend não é carteira.

**Desempenho.** Separar o financeiro desacopla a carga e o domínio. Isso não garante, por si só, desempenho melhor. A integração e as consultas de Plano e uso são medidas e testadas como as outras rotas ([12 Desempenho](12-desempenho.md), [ADR-0052](../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)).

## 11. Legado de pagamentos

O código atual tem o legado do MP-027. A situação dele é da [FN-068](../funcionalidades/068-financeiro/planejamento.md) §6.3.

**[Diretriz]** ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §7) Esta documentação não remove nem desliga o legado. O destino dele está **pendente, a investigar e decidir em tarefa própria** (FN-068, subfase 3.1). Desativar checkout, assinaturas, webhook, job, middleware, dados ou tabelas exige investigação e autorização próprias. A investigação não expõe segredos.

**Inventário.** Os caminhos são relativos a `apps/`. Os itens marcados com ⚠ são **achados de leitura de código**. Eles **não foram comprovados em produção**. Os valores de configuração da homologação e da produção não foram conferidos.

| Item | Evidência | Observação |
|---|---|---|
| Tabelas `planos`, `assinaturas` e `pagamentos` | `api/database/migrations/2026_06_19_000001` a `000003`; `assinaturas.titular_id` referencia `usuarios` (`…000002…:14`) | Titular por conta de acesso (limitação do legado, §1). Valores em `decimal(10,2)` |
| `GET /api/v1/planos` | `api/routes/api.php:37` | Rota pública |
| `POST /api/v1/assinaturas/checkout` | `api/routes/api.php:335`; `api/app/Http/Controllers/Api/AssinaturaController.php:14-18` | Devolve 409 (contratação assistida). Não chama o gateway |
| ⚠ `GET /api/v1/assinaturas/minha` e `POST /api/v1/assinaturas/cancelar` sem middleware de permissão | `api/routes/api.php:333-338`, dentro do grupo `auth:sanctum`, `contexto_vinculo` e `senha_trocada` (`:58`) | Filtram por `titular_id` = usuário (`AssinaturaController.php:26` e `:43`), não pelo vínculo. `cancelar` chama o gateway (`:47-48`) |
| `POST /api/v1/webhooks/mercadopago` | `api/routes/api.php:34`; `api/app/Http/Controllers/Api/WebhookController.php` | Rota pública. Resolve o titular por `explode(':', external_reference)` (`:67`) |
| ⚠ Validação do webhook com segredo vazio | `api/app/Services/MercadoPagoService.php:135-137`; padrão `''` em `api/config/mercadopago.php:6` | Com o segredo vazio, a validação aceita qualquer chamada |
| ⚠ Assinatura do webhook não cobre o corpo | `MercadoPagoService.php:151-152` | O texto assinado usa o `x-request-id` e o horário. Ele não inclui o corpo nem o id do recurso. A investigação confere se isso segue o formato do provedor |
| ⚠ Pré-aprovação aceita o status do corpo | `WebhookController.php:123` e `:130-131` | A pré-aprovação cancela a assinatura com o `status` do corpo, sem consultar o gateway. O pagamento consulta o gateway (`:56`) |
| ⚠ `VerificarInadimplenciaJob` agendado | `api/routes/console.php:12` (todo dia às 03:00); `api/app/Jobs/VerificarInadimplenciaJob.php:29` e `:40` | O job muda o status de assinaturas para `atrasada`. Nenhum bloqueio novo sem regra aprovada (FN-068 §8) |
| ⚠ Middleware `assinatura_ativa` registrado e sem uso | Alias em `api/bootstrap/app.php:25`; classe `api/app/Http/Middleware/CheckAssinaturaAtiva.php` (402 em `:56`) | Nenhuma rota o aplica (busca em `apps/`). Ele usa `escopos()` antigo (`:25`), fora do modelo de vínculos |
| ⚠ Retorno do checkout declara pagamento | `web/routes/web.php:43-46` (rotas públicas); `web/app/Http/Controllers/CheckoutController.php:26`; `web/resources/views/checkout/sucesso.blade.php` | `sucesso` lê `status` da URL, com padrão `approved`. A página diz "Pagamento confirmado!" sem consultar nada. Ela não muda dado. Contraexemplo da regra do §13 |
| Web `/checkout` e `/assinatura` | `web/routes/web.php:40-41` e `:308-309` | `/checkout` redireciona ao Fale Conosco |
| Menu "Assinatura e cobrança" | `web/resources/views/layouts/app.blade.php:46` | Aparece para quem tem `auth_titular` fora do contexto interno. Ponto da transição para Plano e uso (FN-068) |

## 12. Pendências deste contrato

| Pendência | Onde é tratada |
|---|---|
| Protocolo, endpoints e formatos | Projeto do aplicativo financeiro, com revisão deste documento |
| Mecanismo de notificação (§7) e origem do evento de alteração de limite no extrato | Projeto do aplicativo financeiro; decisão do responsável |
| Provedor e protocolo de identidade entre os produtos (§13.5) | Decisão do responsável |
| Mecanismo da intenção de travessia (§13.1) | Decisão do responsável |
| Perfil do representante institucional e do financeiro institucional autorizado; quem vê o resumo institucional (§4) | [04](04-identidade-acesso-e-permissoes.md) §9.1; [FN-068 §8](../funcionalidades/068-financeiro/planejamento.md) |
| Papel futuro do perfil interno `financeiro` (§8) | FN-068 §8 |
| Comportamento além da referência de uso livre (§4) | FN-068 §8 |
| Modelo de distribuição, reservas, fim de ciclo e professor sem limite (§4) | FN-068 §8; [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Chave exata da permissão do detalhe pedagógico (§5) | FN-081 §6.5; até a demonstração da proteção |
| Valor monetário do plano vigente | FN-068 §8. Não é proibido nem exigido |
| Cache das consultas externas (§3.2) | Planejamento de implementação, com medição de 1.000 usuários |
| Paginação e intervalo máximo do extrato (§3.2) | Contrato externo |
| Protocolo de contingência (§10) | Decisão do responsável |
| Entidade de aplicação e formato do `correction_subject_id` | [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Quantidades por plano (D-3) e formato da compra avulsa (D-4) | [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Pendências jurídicas 1 (compra, arrependimento e reembolso) e 2 (autoria e licença das questões) | [FN-068 §8](../funcionalidades/068-financeiro/planejamento.md) |
| Destino do legado e investigação dos achados do §11 | FN-068, subfase 3.1, em tarefa própria |
| Piloto em produção com o simulador | §9.4; decisão expressa do responsável. Não habilitado |
| Infraestrutura, marca definitiva e meios de pagamento do aplicativo financeiro | Fora deste repositório |
| Retenção do registro operacional (§6, item 10) e da intenção de travessia (§13.1) | Decisão do responsável, com o [11 Segurança e privacidade](11-seguranca-e-privacidade.md) (seção "Retenção") |
| Inclusão do aplicativo financeiro no [11](11-seguranca-e-privacidade.md): lista de terceiros, cláusulas de operador e RIPD | Tarefa própria |

## 13. Travessia para a Central do cliente e retorno

**Nada desta seção está implementado.** A Central do cliente não existe. Esta seção não escolhe protocolo, endpoint nem tecnologia. As condições de segurança são B5 e B6 do [ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §5.

### 13.1 Intenção

**[Diretriz]** ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §4) Uma ação nova usa o vínculo ativo ([ADR-0024](../decisoes/0024-vinculo-ativo-acompanha-a-sessao.md)). Uma ação iniciada mantém o contexto e o destinatário originais, mesmo depois de uma troca de vínculo. O destinatário aparece antes da confirmação externa. Não há consumo automático de outra conta nem transferência entre contextos.

**[Proposto]**

1. A API do Gabarito360 cria a intenção antes da saída. A Web não cria nem altera a intenção.
2. A intenção fica ligada, de forma imutável, a pessoa, vínculo, contexto de cobrança e tipo de ação. Os dados enviados são os do §5.
3. A tela mostra o destinatário (a conta beneficiada e o contexto) antes da saída. A pessoa confirma a saída.
4. Uma troca de vínculo depois da criação não muda a intenção. O vínculo novo não herda a intenção.
5. Uma tela antiga, depois de uma troca legítima de vínculo, não cria intenção com o vínculo novo. Ela recebe a recusa que o [04](04-identidade-acesso-e-permissoes.md) §4.3, item 6, já define (409).
6. A intenção não é compra, débito nem saldo. Ela não autoriza nada no aplicativo financeiro. O aplicativo financeiro confere identidade, papel e contrato antes de qualquer ação.

**[Pendente]** O mecanismo da intenção. Opções registradas: (a) registro operacional local da intenção, sem valor financeiro; (b) referência assinada sem estado local; (c) estado só no aplicativo financeiro. A escolha depende da retomada da tarefa e da auditoria.

### 13.2 Retorno

**[Proposto]**

1. O destino de retorno vem de uma lista fechada, configurada por ambiente no servidor. A URL nunca leva um endereço livre de retorno.
2. A URL de retorno leva só uma correlação opaca. O Gabarito360 confere a correlação contra a pessoa e o vínculo da sessão.
   - A correlação é de uso único e de curta validade. Ela nunca funciona sozinha como credencial: sem a sessão da mesma pessoa, ela não abre nada.
   - A query string pode ir ao log de acesso do servidor web. O formato `g360_privado` (`infra/nginx.conf:11`) grava só o caminho, sem a query string. O formato padrão do nginx grava a query string, e `deploy/nginx/gabarito360.conf:10` não declara formato próprio. Por isso, a correlação não pode ter valor depois do uso.
   - O app já envia `Referrer-Policy: no-referrer` ([11 Segurança e privacidade](11-seguranca-e-privacidade.md), seção "Cabeçalhos"; `apps/web/app/Http/Middleware/ProtegerRespostas.php:13`). Isso reduz o vazamento pelo `Referer`.
   - Na implementação, confira que o nome do parâmetro não colide com a lista do `RecusarCredenciaisNaUrl` (`apps/web/app/Http/Middleware/RecusarCredenciaisNaUrl.php:11-12`). Um nome da lista faz o pedido ser recusado com 400.
3. Nenhum ID de URL autoriza nada. Nenhum ID de URL escolhe vínculo, contexto ou conta.
4. Um retorno que chega com outro vínculo ativo mostra só um aviso neutro. Ele não troca o vínculo e não mostra dados do outro contexto.
5. **[Diretriz]** Voltar do checkout não prova pagamento. Ao voltar, a tela mostra "Retorno pendente de confirmação" e consulta o estado pela porta (§3.2). O legado faz o contrário (§11, retorno do checkout). O desenho novo não repete esse padrão.

### 13.3 Confirmação, atraso, repetição e retomada

**[Proposto]**

1. Só a confirmação do aplicativo financeiro muda o estado da contratação. Ela é autenticada, correlacionada pela chave da intenção e idempotente (§7).
2. A confirmação pode atrasar. A tela tolera a espera e oferece "verificar de novo". Ela não promete prazo.
3. Uma confirmação repetida não muda nada.
4. Estados da contratação vistos pelo Gabarito360: pendente de confirmação, confirmada, cancelada, falha e solicitação de ajuste em análise. Os nomes são definidos com o aplicativo financeiro. A exibição é da [FN-068](../funcionalidades/068-financeiro/planejamento.md).
5. Depois da confirmação, a tarefa de origem (por exemplo, um envio ao modo servidor recusado por falta de disponibilidade) fica disponível de novo. O Gabarito360 nunca a reenvia de forma automática. A pessoa decide.

### 13.4 Segredos e auditoria

**[Proposto]**

1. Nenhum segredo, token de sessão ou credencial vai em URL, log ou cabeçalho `Referer`.
2. A emissão da travessia é auditada, sem token. A auditoria geral é a do [11 Segurança e privacidade](11-seguranca-e-privacidade.md) (seção "Auditoria geral", middleware `RegistrarAuditoria`, em `apps/api/app/Http/Middleware/RegistrarAuditoria.php`).
3. O Gabarito360 não recebe nem guarda dado de pagamento, meio de pagamento ou fatura na travessia.

### 13.5 Identidade na Central do cliente

**[Diretriz]** O representante institucional pode entrar direto na Central do cliente, sem passar por funções docentes ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §1).

**[Pendente]** O protocolo de identidade entre os produtos. Alternativas registradas, sem escolha:

| Alternativa | Como funciona | Risco principal |
|---|---|---|
| Login separado | A Central tem conta e autenticação próprias | Mais atrito; contas que não correspondem à mesma pessoa |
| Federação | Uma identidade comum aos dois produtos | Um comprometimento do provedor alcança as ações financeiras; a sessão federada não prova vínculo nem papel financeiro |
| Passagem de curta duração | Referência de uso único, de curta validade, ligada ao destino, que leva só o contexto | Repetição, vazamento por log e fixação de sessão |

**[Proposto]** Qualquer que seja a escolha, a Central faz a própria autenticação antes de uma ação que muda estado (compra, mudança de plano, capacidade ou limite). Uma passagem do Gabarito360 leva contexto, não autorização.
