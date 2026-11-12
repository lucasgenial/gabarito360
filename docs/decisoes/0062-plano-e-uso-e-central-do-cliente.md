---
id: ADR-0062
titulo: O Gabarito360 mostra Plano e uso por vínculo; a contratação e a gestão comercial ficam na Central do cliente, no aplicativo externo
estado: Aceita
substituida_por: []
data: 2026-11-12
afeta_funcionalidades: [FN-068, FN-081]
afeta_caminhos: ["apps/api/**", "apps/web/**"]
verificacao: revisao-ia
regras: []
agents_secao: Planejamento
agents_ordem: 70
agents_resumo:
  - "O financeiro (catálogo, planos, assinaturas, pagamentos, créditos, bônus, franquias, reservas, consumo e auditoria financeira) é um aplicativo externo independente, com código, banco, credenciais e implantação próprios. Nome de trabalho: M3 Core, sem marca escolhida. Ele é futuro e não bloqueia o Gabarito360. No mesmo aplicativo externo ficam a Central do cliente (professor autônomo e representante institucional: adicionais, mudança de plano, contratação, capacidade institucional e solicitações) e a administração financeira da empresa, com permissões próprias. Plano e uso é a área do Gabarito360 que mostra, por vínculo, as informações autorizadas recebidas do aplicativo externo: plano vigente ou cobertura institucional, recursos, ciclo, quantidades, percentuais aplicáveis, disponibilidade, operações em processamento, consumo e extrato. Ela só oferece entradas contextuais para a Central do cliente. Ela não é administração financeira: não crie nela catálogo, preço calculado, saldo próprio nem edição de limite, alocação, saldo ou plano."
  - "O Gabarito360 fala com o financeiro só pela porta `BillingClient`, por integração. O contrato está em `docs/arquitetura/14-integracao-financeira.md`. Não crie no Gabarito360 carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo nem esquema financeiro local. O `LocalBillingAdapter` foi superado. O Gabarito360 não acessa o banco financeiro nem guarda credencial administrativa ou segredo de gateway. O aplicativo financeiro autentica o Gabarito360 e autoriza cada ação. Ações humanas financeiras exigem identidade e permissão próprias, confirmadas pelo aplicativo externo. Não deduza papel financeiro de perfil pedagógico ou de direção."
  - "Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos. Plano e uso consulta pelo vínculo ativo, sem mudar a titularidade contratual. Uma ação nova usa o vínculo ativo. Uma ação iniciada mantém o contexto e o destinatário originais, mesmo depois de uma troca de vínculo. Mostre o destinatário antes da confirmação externa. A modalidade (plano pessoal, uso livre monitorado ou capacidade limitada) vem do contrato, nunca da natureza pública ou privada da escola. No uso livre, não crie bloqueio nem indicador de esgotamento por inferência, não aplique percentual à referência de acompanhamento e não sugira dívida."
  - "Cada operação tarifada tem um único contexto (professor, escola ou núcleo), sem recurso automático a outro nem transferência entre contextos. Sem autorização válida, o envio ao servidor é recusado. A queda da API financeira nunca ativa o simulador nem libera tudo. A primeira entrega é um simulador genérico da porta, ativado só por configuração em desenvolvimento e homologação: o processamento educacional é real, e só a resposta financeira é simulada, sem cobrança real nem retroativa. O aparelho só exibe, de forma temporária, dados financeiros autorizados: ele não os persiste e não decide autorização. Não envie ao aplicativo externo nome de aluno, resposta, nota nem outro dado pedagógico. Voltar do checkout não prova pagamento. Não desligue o legado do MP-027 sem tarefa própria."
---
# ADR-0062 — O Gabarito360 mostra Plano e uso por vínculo; a contratação e a gestão comercial ficam na Central do cliente, no aplicativo externo

**Data:** 2026-11-12
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** autorização do responsável de 12/11/2026 para a documentação de Plano e uso (reorientação da FN-068 para Plano e uso); [ADR-0061](0061-arquitetura-de-billing-e-porta-billingclient.md), que esta decisão substitui em parte
**Afeta:** [FN-068](../funcionalidades/068-financeiro/planejamento.md), [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md); [14 Integração com o aplicativo financeiro](../arquitetura/14-integracao-financeira.md); [04 Identidade, acesso e permissões](../arquitetura/04-identidade-acesso-e-permissoes.md); [11 Segurança e privacidade](../arquitetura/11-seguranca-e-privacidade.md); [Glossário](../glossario.md)

## Contexto

O [ADR-0061](0061-arquitetura-de-billing-e-porta-billingclient.md) pôs o financeiro num aplicativo externo independente e deu ao Gabarito360 um "módulo financeiro limitado". Esse texto deixava em aberto onde ficam a compra, a gestão de limites e as outras ações humanas financeiras. O contrato 14 §8 registrava essa dúvida como pendência.

Em 12/11/2026, o responsável mudou a finalidade da área. O Gabarito360 só mostra o plano, a cobertura e o uso de cada vínculo. A contratação e a gestão comercial ficam numa Central do cliente, no aplicativo externo. O responsável também separou as modalidades de contratação e os conceitos de pessoa, conta, vínculo e contratação.

Por isso, o mockup `mockups/financeiro.*` do PR #275 (SHA `4a10ea2d`) é uma proposta superada por mudança de finalidade. Ele não tem defeito. O PR é fechado sem merge, e a branch e o SHA ficam preservados.

## Decisão

As marcas usadas abaixo são as do [contrato](../arquitetura/14-integracao-financeira.md) §0: **[Diretriz]**, **[Histórico]**, **[Proposto]** e **[Pendente]**. Só o texto do responsável tem a marca [Diretriz].

Texto do responsável (12/11/2026), na parte que trata desta decisão:

> "Mantenha a reorientação da FN-068, número e pasta; o novo ADR com sucessão parcial e preservação das regras válidas; os documentos donos; os agentes e revisões independentes. Propostas e pendências não viram decisões."
>
> "Plano e uso exibe informações autorizadas do plano vigente, recursos, ciclo, quantidades, percentuais aplicáveis e disponibilidade, sem catálogo, preços calculados ou saldo próprios. Valor monetário permanece pendente, sem bloquear a documentação. Uso livre não ganha bloqueio nem indicador de esgotamento por inferência."
>
> "Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos. Ações iniciadas mantêm o contexto original."
>
> "Esclareça B4: referências opacas limitam os dados pedagógicos; não proíbem os dados de cobertura e consumo necessários. Detalhes de aluno/aplicação exigem autorização local existente e validada; na dúvida, negue o detalhe."

### 1. Aplicativo externo, Central do cliente e administração financeira **[Diretriz]**

- O aplicativo financeiro continua externo e independente, como no ADR-0061 §1. Ele tem código-fonte, banco, serviço, credenciais e implantação próprios.
- A **Central do cliente** fica no aplicativo externo. Ela atende o professor autônomo e o representante institucional. Nela, o cliente compra adicionais, vê opções e muda de plano, gere a contratação, gere a capacidade institucional e acompanha solicitações.
- O representante institucional pode entrar direto na Central, sem passar por funções docentes.
- A **administração financeira da empresa** fica no mesmo aplicativo externo, com permissões e experiência próprias.
- Isso não exige três aplicativos. Isso não escolhe stack nem marca. "M3 Core" é nome de trabalho, não marca.

### 2. Plano e uso no Gabarito360 **[Diretriz]**

Esta seção substitui o §2 do ADR-0061 ("O módulo financeiro do Gabarito360 é limitado").

- **Plano e uso** é a área do Gabarito360 que mostra, por vínculo, as informações autorizadas recebidas do aplicativo externo: plano vigente ou cobertura institucional, recursos incluídos, ciclo, quantidades, percentuais aplicáveis, disponibilidade, operações em processamento, consumo (mês, ano e intervalo) e extrato.
- Ela oferece entradas contextuais para a Central do cliente.
- Ela não é um módulo de administração financeira. O Gabarito360 não mantém catálogo comercial, não calcula preços e não é autoridade sobre saldo ou franquia.
- **[Pendente]** O valor monetário do plano vigente. Ele não é proibido nem exigido. Ele não bloqueia a documentação.
- O menu-alvo é uma entrada única "Plano e uso" no menu da conta, ao fim da transição. A transição a partir de "Assinatura e cobrança" (legado) é documentada na [FN-068](../funcionalidades/068-financeiro/planejamento.md). Esta decisão não muda rota, menu nem código.

Continuam do ADR-0061 §2 **[Diretriz]**: o Gabarito360 tem a porta `BillingClient` e o cliente da API, a identidade da correção (`correction_subject_id`) e o registro operacional dela, as referências externas opacas e a exibição dos dados recebidos. A exibição passa a ser por vínculo.

Os estados da área, os indicadores e o extrato são da [FN-068](../funcionalidades/068-financeiro/planejamento.md). As capacidades de consulta são do [contrato](../arquitetura/14-integracao-financeira.md).

### 3. Modalidades e governança institucional **[Diretriz]**

Esta seção altera o lugar e a forma da governança institucional do ADR-0061 §5. As regras de contexto de cobrança do §5 continuam.

| Modalidade | Regra |
|---|---|
| Professor autônomo | Plano pessoal. |
| Instituição com **uso livre monitorado** | Há medição. A instituição acompanha o uso e pede ajustes. Só o Financeiro do aplicativo externo ajusta, depois de conferir o contrato. A escola não redistribui. |
| Instituição com **capacidade limitada** | O financeiro institucional autorizado administra a capacidade na Central do cliente, dentro do contratado e dos adicionais confirmados. Ele não cria capacidade acima do contratado. Consumo confirmado e reservas em processamento não são apagados. |

- A modalidade vem do contrato, nunca da natureza (pública ou privada) da escola.
- Referência de acompanhamento, limite bloqueante e cobertura contratual são conceitos diferentes.
- No **uso livre**, o Gabarito360 não cria bloqueio nem indicador de esgotamento por inferência. O percentual não se aplica à referência de uso livre. Nada sugere dívida por ultrapassar a referência.
- **[Pendente]** O comportamento ao ultrapassar a referência de uso livre.

A administração de limites e de capacidade sai do Gabarito360. Ela acontece na Central do cliente, conforme a modalidade.

### 4. Conceitos distintos e contexto da ação **[Diretriz]**

- Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos. As definições são do [04](../arquitetura/04-identidade-acesso-e-permissoes.md) e do [Glossário](../glossario.md). A conta contratante na integração é do [contrato](../arquitetura/14-integracao-financeira.md).
- O extrato por vínculo não muda a titularidade contratual. O contrato pode ser da conta contratante da escola, e cada professor consulta pelo seu vínculo.
- Uma ação **nova** usa o vínculo ativo ([ADR-0024](0024-vinculo-ativo-acompanha-a-sessao.md)).
- Uma ação **iniciada** mantém o contexto e o destinatário originais, mesmo depois de uma troca de vínculo.
- O destinatário aparece antes da confirmação externa.
- Não há consumo automático de outra conta nem transferência entre contextos.

### 5. Fronteira de confiança, papéis e dados **[Diretriz]**

O ADR-0061 §4 continua inteiro: o aplicativo financeiro autentica o Gabarito360 e autoriza cada ação; a credencial técnica não administra o financeiro; ações humanas financeiras exigem identidade, vínculo e permissão próprios; os produtos não compartilham banco, usuário administrativo de banco nem segredos de gateway.

O responsável manteve as condições de segurança B1 a B6. Os documentos donos as descrevem. Aqui fica só o princípio de cada uma:

- **B1.** A titularidade contratual é da conta contratante, no aplicativo externo. O legado do MP-027 usa a conta de acesso como titular. Isso é limitação do legado, não regra do modelo novo.
- **B2.** Nenhum papel financeiro é deduzido de perfil pedagógico ou de direção. O aplicativo externo confirma o papel em cada ação. Esta decisão não cria chave de permissão.
- **B3.** O perfil interno `financeiro` não tem tela em Plano e uso. A visão de vários clientes é da administração financeira da empresa, no aplicativo externo.
- **B4.** O aplicativo externo fornece os dados de cobertura, disponibilidade, consumo, unidade, situação e hora da atualização. Ele não recebe nem devolve nome de aluno, resposta, nota ou outro detalhe pedagógico. As referências opacas limitam os dados pedagógicos; elas não proíbem os dados de cobertura e consumo necessários. O Gabarito360 resolve aluno e aplicação no próprio banco, no momento do pedido, com autorização local existente e validada. Na dúvida, o detalhe é negado.
- **B5.** O servidor cria a intenção da travessia para a Central e a liga, de forma imutável, a pessoa, vínculo, contexto de cobrança e tipo de ação.
- **B6.** Voltar do checkout não prova pagamento. A confirmação externa é autenticada, correlacionada e idempotente. Nenhum ID de URL autoriza nada.

Os controles técnicos de B4 a B6 (lista fechada de retorno, correlação opaca, auditoria da emissão, ausência de segredo em URL, log ou `Referer`) são **[Proposto]** e ficam no [contrato](../arquitetura/14-integracao-financeira.md). A regra do detalhe pedagógico é da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md) §6.5.

### 6. Lugar das ações humanas financeiras **[Diretriz]**

A pendência "lugar das ações humanas" (contrato 14 §8; FN-068 §8) está decidida: as ações humanas financeiras ficam na Central do cliente, no aplicativo externo. O Gabarito360 só oferece a entrada contextual.

**[Pendente]** O provedor de identidade e o protocolo de identidade entre os produtos continuam em aberto.

### 7. Legado **[Diretriz]**

O ADR-0061 §7 continua. O destino do legado do MP-027 (tabelas, rotas, webhook, checkout, job e middleware) está pendente e é decidido em tarefa própria. Nada é desligado. Os achados de segurança do Mercado Pago só são registrados. A investigação é tarefa própria, sem expor segredos.

## Continuidade das regras do ADR-0061

| Regra anterior (ADR-0061) | Situação | Onde está a regra vigente |
|---|---|---|
| §1: aplicativo financeiro externo e independente; sem acesso ao banco financeiro | Preservada | §1 deste ADR; [contrato](../arquitetura/14-integracao-financeira.md) §1 |
| §1: direção funcional do aplicativo externo (catálogo, planos, assinaturas, pagamentos, créditos, bônus, franquias, recompensas, reservas, distribuição institucional, auditoria) | Preservada e ampliada com a Central do cliente e a administração financeira da empresa | §1 deste ADR; contrato §1 |
| §1: nome de trabalho M3 Core; aplicativo futuro que não bloqueia o Gabarito360 | Preservada | §1 deste ADR |
| §2: finalidade do "módulo financeiro limitado" | **Substituída** por Plano e uso: consulta por vínculo e entradas contextuais | §2 deste ADR; [FN-068](../funcionalidades/068-financeiro/planejamento.md) |
| §2: o que o Gabarito360 tem (porta, cliente da API, `correction_subject_id`, registro operacional, referências opacas, exibição) | Preservada; a exibição passa a ser por vínculo | §2 deste ADR; contrato §2 |
| §2: proibições (carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo, esquema financeiro local) | Preservada | Consequências deste ADR; contrato §2 |
| §3: o contrato tem um dono | Preservada | [Contrato 14](../arquitetura/14-integracao-financeira.md) |
| §4: fronteira de confiança | Preservada; acrescida de B2 e B3 | §5 deste ADR; contrato §8 |
| §5 [Histórico]: termos Núcleo, Escola e Professor; contexto único; carteiras isoladas; hierarquia sem consumo implícito; alocação explícita | Preservada | Contrato §4 |
| §5 [Diretriz]: o responsável financeiro institucional compra créditos e administra limites mensais por professor | **Alterada** no lugar e na forma: a governança fica na Central do cliente e depende da modalidade | §3 deste ADR; contrato §4 |
| §6: recusa sem autorização; queda da API não ativa o simulador; simulador só em desenvolvimento e homologação; processamento educacional real | Preservada | Consequências deste ADR; contrato §9 e §10 |
| §6: aparelho só exibe de forma temporária e não persiste | Preservada | Consequências deste ADR; contrato §2 |
| §6 [Pendente]: piloto em produção com simulação | Preservada como pendência | Pendências deste ADR; contrato §9.4 |
| §7: legado do MP-027 não é desligado sem tarefa própria | Preservada; destino do legado pendente | §7 deste ADR; FN-068 |
| Revisão de 12/11/2026: `LocalBillingAdapter`, esquema `billing_`, migração por cópia, lease local e dono temporário do esquema | Continuam superados | Registro no ADR-0061 |
| Revisão de 12/11/2026: porta `BillingClient` e capacidades `authorize`, `reserve`, `commit`, `release` e `getBalance` | Preservada | Contrato §2 e §3 |
| Revisão de 12/11/2026: cliente da API do aplicativo financeiro (antes `M3CoreBillingAdapter`) | Preservada | Contrato §2 |
| Revisão de 12/11/2026: Billing não é Payments; catálogo sem código por produto; lotes, validade, ordem de consumo, append-only, reversão e compensação | Preservada como requisitos do aplicativo externo | Contrato §1 e §6; FN-081 §6.5 |
| Revisão de 12/11/2026: contexto único e alocação explícita; recusa sem franquia e sem crédito | Preservada | Contrato §4 e §7; FN-081 |
| Revisão de 12/11/2026: perfil interno Financeiro "continua em parte" | **Alterada em parte**: o perfil não tem tela em Plano e uso (B3); o papel futuro continua pendente | §5 deste ADR; FN-068 §8; [04](../arquitetura/04-identidade-acesso-e-permissoes.md) |
| Contrato 14 §8 [Pendente]: lugar das ações humanas | **Decidida**: Central do cliente. O provedor de identidade continua pendente | §6 deste ADR; contrato §8 |
| Consequências: todas as obrigações e proibições | Preservadas | Consequências deste ADR |
| Consequências: "A experiência financeira limitada, a integração e o legado são da FN-068" | **Alterada**: Plano e uso, a integração do lado do Gabarito360 e o legado são da FN-068 | Consequências deste ADR |

O texto do ADR-0061 não muda. Só as linhas "Atualizado em" e "Estado" do corpo e o cabeçalho de campos registram esta substituição parcial.

## Pendências

Estas pendências são do responsável. Esta decisão não as fecha.

| Pendência | Onde é tratada |
|---|---|
| Valor monetário do plano vigente | [FN-068 §8](../funcionalidades/068-financeiro/planejamento.md) |
| Perfil do representante institucional: quem vê o resumo institucional e quem abre a Central pela instituição | [04](../arquitetura/04-identidade-acesso-e-permissoes.md); FN-068 §8 |
| Papel futuro do perfil interno `financeiro` | FN-068 §8; 04 |
| Comportamento além da referência de uso livre | FN-068 §8; contrato §4 |
| Modelo de distribuição, reservas, fim de ciclo e professor sem limite | Contrato §4; [FN-081 §8](../funcionalidades/081-creditos-correcao/planejamento.md) |
| Protocolo de identidade entre os produtos. Alternativas: login separado; federação; passagem de curta duração só para contexto, com autenticação própria da Central antes de ações que mudam estado | Contrato §8 |
| Mecanismo da intenção de travessia | Contrato (travessia) |
| Mecanismo de notificação | Contrato §7 |
| Chave exata da permissão do detalhe pedagógico, até a demonstração da proteção | FN-081 §6.5; FN-068 |
| Destino do legado e achados de segurança do Mercado Pago | Tarefa própria; FN-068 §6.3 |
| Piloto em produção com simulação | Contrato §9.4 |
| Cache das consultas externas | Contrato; [12 Desempenho](../arquitetura/12-desempenho.md), com medição de 1.000 usuários ([ADR-0052](0052-medicao-com-1000-usuarios-simultaneos.md)) |

As demais pendências (formato do `correction_subject_id`, D-3, D-4, preços, planos, pacotes, questões jurídicas, tela de passagem, paginação do extrato) estão nos documentos donos: FN-081 §8, FN-068 §8 e contrato §12.

## Consequências

**Obrigatório:**

- Todo consumo tarifado passa pela porta `BillingClient`, segundo o [contrato](../arquitetura/14-integracao-financeira.md).
- Plano e uso mostra só dados autorizados recebidos do aplicativo externo, pelo vínculo ativo.
- Uma ação iniciada guarda o contexto e o destinatário originais. A tela mostra o destinatário antes da saída para a Central.
- A modalidade vem do contrato.
- A API real implementa a mesma porta `BillingClient` que o simulador. Ela é validada contra o contrato antes da troca.
- A identidade da cobrança da correção é `correction_subject_id` (aluno × aplicação) ([ADR-0058](0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md), item 8).
- As regras funcionais da correção são da [FN-081](../funcionalidades/081-creditos-correcao/planejamento.md).
- Plano e uso, a integração do lado do Gabarito360 e o legado são da [FN-068](../funcionalidades/068-financeiro/planejamento.md).
- O aplicativo financeiro, a Central do cliente e a documentação interna deles não são criados neste repositório. Eles são dependência externa.

**Proibido:**

- Criar no Gabarito360 carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo ou esquema financeiro local.
- Criar no Gabarito360 catálogo comercial, preço calculado ou saldo próprio.
- Editar no Gabarito360 limite, alocação, saldo, capacidade ou plano.
- Mostrar no Gabarito360 informação da administração financeira da empresa, como a visão de vários clientes.
- Deduzir papel financeiro de perfil pedagógico ou de direção.
- Criar bloqueio, indicador de esgotamento ou percentual por inferência no uso livre.
- Enviar ao aplicativo externo nome de aluno, resposta, nota ou outro dado pedagógico.
- Tratar o retorno do checkout como prova de pagamento.
- Conectar o Gabarito360 ao banco do aplicativo financeiro.
- Guardar no Gabarito360 credencial administrativa do aplicativo financeiro ou segredo de gateway.
- Ativar o simulador, ou liberar o consumo, porque a API financeira caiu.
- Ativar o simulador em produção sem decisão expressa do responsável.
- Persistir no aparelho (armazenamento do navegador ou do aparelho) os dados financeiros exibidos.
- Manter no cliente saldo autoritativo, carteira, concessão de contingência ou autorização financeira.
- Expor ao cliente credenciais técnicas ou administrativas.
- Desligar o legado do MP-027 sem tarefa própria autorizada.

**Substituição:**

- Esta decisão substitui em parte o [ADR-0061](0061-arquitetura-de-billing-e-porta-billingclient.md). Mudam a finalidade do §2 e o lugar e a forma da governança institucional do §5. Os §1, §3, §4, §6 e §7 e as Consequências continuam vigentes e estão reafirmados aqui.
- Esta decisão fecha a pendência "lugar das ações humanas" do contrato 14 §8 e da FN-068 §8.

## Verificação

Revisão de IA (`revisao-ia`): em cada PR da FN-068 e da FN-081, o agente de conformidade confere os pontos abaixo. Os pontos 1 a 7 vêm do ADR-0061.

1. O código fala com o financeiro só pela porta `BillingClient`.
2. Nenhuma migration do Gabarito360 cria carteira, ledger, lotes, saldo, preço ou tabela financeira nova. O estado do simulador fica separado das tabelas do produto.
3. Nenhuma configuração do Gabarito360 tem conexão ao banco financeiro, credencial administrativa ou segredo de gateway.
4. Nenhum caminho de código ativa o simulador, ou autoriza consumo, quando a API financeira falha. A configuração de produção não ativa o simulador.
5. O cliente só exibe dados financeiros autorizados, de forma temporária. Ele não os persiste, não guarda saldo autoritativo, carteira nem concessão de contingência, não decide autorização e não recebe credenciais técnicas ou administrativas.
6. O legado do MP-027 só muda em tarefa própria, autorizada.
7. A troca do simulador pela API real só ocorre depois de a API real passar na validação contra o contrato.
8. Nenhuma tela, rota ou formulário do Gabarito360 edita limite, alocação, capacidade, saldo ou plano. Nenhum código calcula preço nem mantém catálogo comercial.
9. Nenhuma tela do Gabarito360 mostra informação da administração financeira da empresa. O perfil interno `financeiro` não tem tela em Plano e uso. Nenhum código soma registros de vários clientes.
10. A consulta de Plano e uso é derivada do vínculo ativo da sessão, no servidor. Nenhum identificador vindo do navegador escolhe o vínculo ou o contexto. Um teste prova que uma ação iniciada mantém o destinatário original depois de uma troca de vínculo.
11. Nenhuma chamada ao aplicativo externo envia nome de aluno, resposta, nota ou outro dado pedagógico. O detalhe de aluno e aplicação no extrato usa autorização local existente e demonstrada. Sem essa demonstração, a tela mostra "Dado restrito".
12. Nenhum código trata o retorno do checkout como pagamento. O estado da contratação vem só da confirmação externa autenticada.
13. No uso livre, nenhum código cria bloqueio, indicador de esgotamento ou percentual sobre a referência de acompanhamento.
14. Nenhuma chave de permissão financeira nova entra no catálogo de permissões sem decisão do responsável.

Os testes obrigatórios do consumo da correção estão na [FN-081 §6.7](../funcionalidades/081-creditos-correcao/planejamento.md).
