# 068 — Financeiro (experiência limitada, integração e legado) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 18
**Posição no plano mestre:** Onda 4 (proposta do §5 do [plano mestre](../../planejamento/00-plano-mestre.md)): 068 financeiro, depois 069 selo; depende de decisão comercial e do aplicativo financeiro externo
**Natureza:** evolução de existente (o legado de assinatura do MP-027 está ativo no código e é documentado a partir dele)
**Origem:** tronco original — pacote financeiro F1–F3 da Fase 5, regras comerciais da escola autônoma e a reconciliação FIN. Os documentos antigos (`docs/28`, `docs/163`, `docs/164`, `docs/176` e `docs/06`) foram removidos; os destinos estão no [mapa dos documentos antigos](../../historico/mapa-documentos-antigos.md). Decisões de billing: [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisado em 12/11/2026
**Atualizado em:** 2026-11-12, por lucasgenial

> Escreva este documento no padrão ASD-STE100 moderado (AGENTS.md): uma ideia por frase, voz ativa, frases curtas.

Esta funcionalidade é dona de três assuntos no Gabarito360:

1. a **experiência financeira limitada** (o que o usuário vê e de onde ele vai para uma ação financeira);
2. o **módulo de integração** (porta `BillingClient`, cliente da API e simulador de integração);
3. a **situação do legado** de pagamentos do MP-027.

O financeiro é um aplicativo externo independente ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)). O contrato da integração é do [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). As regras da correção são da [FN-081](../081-creditos-correcao/planejamento.md). Esta FN não é dona da implementação interna do aplicativo financeiro.

Marcas usadas aqui: **[Diretriz]**, **[Histórico]**, **[Proposto]** e **[Pendente]**, com o significado do [contrato](../../arquitetura/14-integracao-financeira.md) §0.

## 1. Objetivo

Dar ao Gabarito360 acesso contextual e limitado às capacidades financeiras que o produto precisa. O usuário vê saldo, limites e histórico do contexto dele. O sistema pede autorização de consumo ao aplicativo financeiro. O Gabarito360 continua focado na finalidade educacional e no desempenho.

## 2. Escopo

- **Inclui:**
  - **Adequação aos princípios** (subfases 0.2 e 0.3): revalidação contra os ADRs vigentes, antes de qualquer código.
  - **Porta `BillingClient`** e **cliente da API** do aplicativo financeiro, conforme o contrato. **[Diretriz]**
  - **Simulador genérico da porta `BillingClient`**, primeira entrega da integração, para desenvolvimento e homologação (contrato §9). O processamento educacional é real; só a resposta financeira é simulada. **[Diretriz]**
  - **Referências externas** opacas: conta e operação no aplicativo financeiro. **[Histórico]**
  - **Experiência financeira limitada:** exibir saldo, limites e histórico recebidos do aplicativo financeiro, no contexto do usuário; e encaminhar o usuário às ações financeiras permitidas. **[Diretriz]**
  - **Situação do legado** do MP-027, sem desligá-lo (§6.3). **[Diretriz]**
  - Regras de produto da escola autônoma que dependem de fato financeiro ([RN-EIN-07 e RN-EIN-08](../../arquitetura/08-regras-de-negocio.md)), sem guardar o fato financeiro no Gabarito360 (§8).
- **Não inclui:**
  - Catálogo comercial, planos, preços, assinaturas, pagamentos, gateways, créditos, bônus, franquias, recompensas, reservas, contabilização, reversões e auditoria financeira → aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §1).
  - Carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo ou esquema financeiro local → proibidos no Gabarito360 (ADR-0061).
  - Criação do aplicativo financeiro, do repositório, das tabelas, da stack ou da documentação interna dele → fora deste repositório.
  - Consumo, reserva, confirmação e liberação da correção, e créditos por contribuição validada → [FN-081](../081-creditos-correcao/planejamento.md).
  - Cadastro de clientes e `contrato_referencia` em texto → [FN-023](../023-cadastro-clientes/planejamento.md) (concluída).
  - Autocadastro, checkout público e MP-029 → [FN-030](../030-saas-autocadastro-assinatura/planejamento.md) (substituída em parte; ver §8).
  - Selo dos documentos por modalidade → [FN-069](../069-selo-documentos/planejamento.md).
  - Adesão da escola autônoma → [FN-029](../029-escola-independente/planejamento.md); encerramento de cliente → [FN-028](../028-encerramento-cliente/planejamento.md).
  - E-mail `assinatura-pagamento` → [FN-060](../060-email-transacional/planejamento.md).
  - ERP escolar, folha de pagamento e mensalidades de estudantes → fora do escopo, sem decisão específica.

**Regra fixa:** nunca guardar número de cartão ou CVV. O Gabarito360 também não guarda tokens de gateway do novo financeiro.

## 3. Fontes de verdade

A ordem das fontes é a do [ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md).

1. **Mockup aprovado:** não existe mockup da experiência financeira limitada. Ele é obrigatório antes da subfase 2.1.
2. **App atual:** as views, os componentes e o CSS de `apps/web` na `main`. As telas do contexto interno de `clientes/*` (FN-023) e a tela legada `/assinatura` são o ponto de partida.
3. **Documentação:**
   - [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) (financeiro externo) e [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md) item 8 (identidade da cobrança da correção).
   - [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md): o contrato.
   - [08 Regras de negócio](../../arquitetura/08-regras-de-negocio.md): RN-GER-09 (Substituída; assinatura e checkout) e RN-EIN-01 a RN-EIN-10 (escola autônoma).
   - [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1: perfil Financeiro e contexto interno.
   - [03 Modelo de dados](../../arquitetura/03-modelo-de-dados.md): tabelas legadas `planos`, `assinaturas` e `pagamentos`.
   - [Operação](../../operacao/README.md): publicação, backup e incidentes.
4. **Design system:** `style-system/` (tokens, componentes e faixas por aparelho).
5. **Históricos:** `mockups/` e `style-system/historico/`. Eles não são referência visual.

Registro histórico da sessão de 11/11/2026: [Decisões de billing, créditos e M3 Core](../../historico/2026-11-11-decisoes-billing.md). Ele não é norma. A parte dele sobre o `LocalBillingAdapter` foi superada (ADR-0061, revisão de 12/11/2026).

Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| ADR-0061 financeiro externo | Define a fronteira e proíbe o financeiro local | Aceita (revisada em 12/11/2026) |
| Contrato de integração ([14](../../arquitetura/14-integracao-financeira.md)) | Capacidades, respostas e falhas da porta | Proposto; protocolo pendente |
| Aplicativo financeiro externo | O cliente da API real só funciona com ele | **Não existe.** Dependência externa; não bloqueia o simulador |
| Decisão comercial (§8) | Preços, franquias por plano e condições de lançamento, no aplicativo financeiro | Pendente |
| FN-021 clientes-espacos | O contexto de cobrança segue o cliente e o espaço | Concluída |
| FN-023 cadastro-clientes | Referência externa da conta do cliente | Concluída |
| FN-013 acessos-internos | Perfil Financeiro e contexto interno | Concluída |
| FN-004 auditoria | Trilha das chamadas de integração | Parcial |
| FN-074 tabelas-padrao | Padrão do histórico exibido | Em homologação |

**Funcionalidades que dependem desta:** FN-081 (usa a porta e o simulador), FN-069 (selo), FN-029 (assunção financeira), FN-028 (encerramento) e FN-030 (preços).

## 5. Fases e subfases

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (MP-027) Legado de assinatura | Tabelas `planos`, `assinaturas` e `pagamentos`; `MercadoPagoService`; `GET /planos`; `assinaturas/minha` e `cancelar`; webhook do Mercado Pago; checkout desativado (409, contratação por contato) | — | Substituída em parte (código ativo) | commit de68d3f; PR #59; `AssinaturaController`, `WebhookController`, `ContratacaoContatoTest` |
| 0.2 Adequação aos princípios | Revalidação contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057, ADR-0058 e ADR-0061 (versão de 11/11); cabeçalho no modelo atual; fronteira com a FN-081 | O campo "Conforme princípios" sobe para a versão vigente | Entregue (documentação) | PR #274, commit `ca6109d5` |
| 0.3 Reorientação para o aplicativo financeiro externo | Escopo trocado: experiência limitada, integração e legado; F1, F2 e F3 antigas superadas; contrato com dono único | "Conforme princípios" na versão 18; nenhum financeiro local no planejamento | Entregue (documentação) | PR #274 |
| 1.1 Primeira entrega da integração: porta `BillingClient` e simulador genérico | Porta; simulador com os cenários do contrato §9.2; estado fictício separado das tabelas do produto, reiniciável e identificado como simulação; ativação explícita só em desenvolvimento e homologação | Os nove cenários do contrato §9.2 passam em testes automatizados; um teste de ponta a ponta percorre login, envio, fila, processamento real, `READY_FOR_REVIEW` e conferência com o simulador; a falha da API real não ativa o simulador (teste); a configuração de produção não ativa o simulador (teste); nenhuma migration financeira | Pendente | — |
| 1.2 Cliente da API do aplicativo financeiro | Implementação real da mesma porta do simulador, com credencial técnica de escopo limitado | O aplicativo financeiro existe; a API real passa nos cenários do contrato §9.2 e na validação contra o contrato antes da troca; `correction_subject_id`, reenvio sem cobrança nova e isolamento dos contextos continuam iguais; os testes de isolamento do contrato §8 passam; medição com 1000 usuários simultâneos ([ADR-0052](../../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)) | Pendente (bloqueada pela dependência externa) | — |
| 2.0 Mockup da experiência financeira limitada | Saldo, limites e histórico do contexto; encaminhamento às ações financeiras; sobre a casca e o CSS reais de `apps/web`, em todas as faixas do ADR-0057 | O responsável aprova o mockup; a revisão de UI/UX é de outro agente (ADR-0020) | Pendente | — |
| 2.1 Telas da experiência financeira limitada | Conforme o mockup aprovado | O usuário vê só o próprio contexto; conferência por texto em todas as larguras de referência | Pendente | — |
| 3.1 Destino do legado do MP-027 | Investigação e proposta para checkout, assinaturas, webhook, dados e tabelas | O responsável autoriza o destino em tarefa própria. Nada é desligado antes | Pendente | — |
| ~~1.1 (F1) Modelo comercial e fiscal~~ | Contratos, faturas, cobranças, pagamentos, notas fiscais, catálogo e assinaturas no banco do Gabarito360 | — | **Superada (12/11/2026).** Direção reservada ao aplicativo financeiro (ADR-0061 §1) | — |
| ~~2.0/2.1 (F2) Telas do Financeiro~~ | Contratos, faturas, baixa manual, inadimplência e painel de recebimentos | — | **Superada (12/11/2026).** Ver §8, item "contratos, faturas e notas fiscais" | — |
| ~~3.1 (F3) Interface de pagamentos~~ | `GatewayPagamento`, `GatewayManual`, adaptação do Mercado Pago e webhook por provedor | — | **Superada (12/11/2026).** Meios de pagamento ficam no aplicativo financeiro | — |

## 6. Contrato técnico (proporcional ao risco)

### 6.1 Integração

- A porta `BillingClient` é a única passagem para o financeiro. O contrato é o [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md).
- O Gabarito360 guarda só referências externas opacas, a chave das operações e o último resultado conhecido de cada operação (contrato §6). **[Proposto]**
- Nenhuma migration desta FN cria carteira, ledger, lotes, saldo, preço ou esquema financeiro. **[Diretriz]**
- A credencial técnica do Gabarito360 fica no servidor, fora do repositório, com escopo limitado às capacidades do contrato §3. Ela não administra o financeiro. **[Diretriz]**
- A indisponibilidade da API financeira nunca ativa o simulador nem libera consumo. **[Diretriz]**
- O simulador só funciona com ativação explícita no servidor, em desenvolvimento e homologação. O estado dele é fictício, separado das tabelas do produto, reiniciável e sem valor comercial. Ele nunca é migrado para o aplicativo financeiro (contrato §9.3 e §9.4). **[Diretriz]**

### 6.2 Autorização e dados

- A tela financeira mostra só o contexto do usuário: pessoal, escola ou núcleo, conforme o vínculo ativo ([ADR-0024](../../decisoes/0024-vinculo-ativo-acompanha-a-sessao.md)).
- O aplicativo financeiro confere a identidade e as permissões da ação humana. O Gabarito360 não decide sozinho que um usuário é responsável financeiro. **[Diretriz]**
- **[Pendente]** O lugar das ações humanas: painel externo, navegação integrada ou tela limitada no Gabarito360 (contrato §8).
- Nenhuma chamada da integração leva dado pedagógico, foto, resposta ou nome de aluno (contrato §5).
- O cliente (navegador ou aparelho) só exibe, de forma temporária, os dados financeiros autorizados. Ele não os persiste e não recebe credenciais técnicas ou administrativas (ADR-0061). **[Diretriz]**
- O perfil interno Financeiro não vê dados de alunos. Hoje ele usa `cliente.ver` e `cliente.gerir`. Chaves novas precisam de aprovação.
- Volume baixo (meta de 100 instituições). Os históricos são paginados.

### 6.3 Situação do legado

O código atual tem o legado do MP-027. Esta FN só documenta. **Nada é removido ou desligado nesta reorientação.** **[Diretriz]**

| Item legado | Onde está | Situação |
|---|---|---|
| Tabelas `planos`, `assinaturas` e `pagamentos` | migrations `2026_06_19_000001` a `000003` | Ativas; valores em `decimal(10,2)`; titular por usuário |
| `GET /planos` | `apps/api/routes/api.php` | Rota pública ativa |
| `GET /assinaturas/minha` e `POST /assinaturas/cancelar` | `apps/api/routes/api.php` | Ativas; só autenticação, sem middleware de permissão |
| `POST /webhooks/mercadopago` | `WebhookController::mercadoPago` | Rota pública ativa, com HMAC |
| Web `/assinatura` e `/checkout/*` | `apps/web` | Checkout redireciona ao Fale Conosco (409 na API) |

Regras:

1. O legado não é o novo financeiro. Ele não vira carteira nem fonte de saldo.
2. O destino do legado (manter, migrar para o aplicativo financeiro ou desativar) é decidido na subfase 3.1, com investigação e autorização próprias.
3. Os dados legados são preservados até essa decisão.

## 7. Interface

O app é responsivo ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)). As telas desta FN atendem a **todas as faixas**: celular pequeno, celular, tablet (retrato e paisagem), notebook, desktop e telas grandes até 4K, com regras próprias para toque.

- Confira cada tela nas larguras 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro.
- Use os cortes canônicos do ADR-0057, `pointer: coarse` e consultas de contêiner. Um corte novo exige registro no design system.
- A referência visual é o app atual ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md)). O design system é `style-system/`.
- A tela mostra o saldo, os limites e o histórico recebidos do aplicativo financeiro. Ela informa a origem do dado e o momento da última atualização. **[Proposto]**
- Sem resposta do aplicativo financeiro, a tela mostra o estado de erro. Ela não mostra saldo calculado no Gabarito360.
- As tabelas seguem a [FN-074](../074-tabelas-padrao/planejamento.md), com a coluna Ações à direita ([ADR-0039](../../decisoes/0039-coluna-de-acoes-a-direita-e-ordenacao.md)).
- Estados obrigatórios em cada tela: carregando, vazio e erro.
- Confirmações usam modal da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)). O JavaScript fica em arquivo externo ([ADR-0023](../../decisoes/0023-javascript-em-arquivos-externos.md)).
- **O mockup vem antes das telas** (subfase 2.0). A revisão de UI/UX é de outro agente ([ADR-0020](../../decisoes/0020-revisao-de-ui-ux-por-especialista.md)).
- A conferência é registrada por texto, sem prints ([ADR-0049](../../decisoes/0049-sem-prints-da-aplicacao.md)).

## 8. Riscos e decisões pendentes do responsável

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| **Fonte da verdade do esquema `billing_`** | (a) Gabarito360 dono até o Core; (b) Core define e o Gabarito360 copia; (c) contrato de esquema externo | — | **Superada (12/11/2026).** O aplicativo financeiro é o dono. O Gabarito360 não tem esquema financeiro ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)). A recomendação (a), registrada em 11/11, nunca foi aprovada |
| **Contratos, faturas, cobranças e notas fiscais do cliente institucional** (antigas F1 e F2) | (a) Ficam no aplicativo financeiro; (b) ficam no Gabarito360 como registro não financeiro | (a). A diretriz reserva pagamentos e auditoria financeira ao aplicativo externo. ⚠ Interpretação desta revisão: contratos, faturas e notas fiscais não aparecem por nome na diretriz | **Pendente** (confirmar) |
| **Papel do perfil interno Financeiro e do cadastro comercial** depois do aplicativo externo | (a) Só navegação ao aplicativo financeiro; (b) consulta limitada no Gabarito360; (c) manter como hoje | — | **Pendente** |
| **Lugar das ações humanas financeiras** (compra de créditos, limites por professor) | Painel externo, navegação integrada ou tela limitada no Gabarito360 | Nenhuma opção guarda credencial financeira global no Gabarito360 (contrato §8) | **Pendente** |
| **Piloto em produção com simulação** | (a) Não ter piloto; (b) piloto com o simulador em produção, com regras próprias | — | **Pendente.** Não habilitado. Sem decisão expressa, a produção não ativa o simulador |
| **Protocolo de contingência** | Ver o contrato §10 | Nenhuma autorização sem serviço disponível ou sem concessão válida | **Pendente** |
| **Conflito: "sem devolução de valores já pagos" × pendência jurídica 1** (arrependimento, CDC art. 49, reembolso e pacote parcialmente consumido) | (a) [RN-EIN-08](../../arquitetura/08-regras-de-negocio.md) vale só no escopo original (transição entre instituições); (b) estender à compra de créditos; (c) suspender a RN-EIN-08 até a revisão jurídica | (a). A compra de créditos por pessoa pode atrair o CDC art. 49. **Conflito registrado até a revisão jurídica.** Separar o aplicativo não encerra a pendência | **Pendente** |
| **Pendência técnica: entidade única de aluno + aplicação** (`correction_subject_id`) | Ver a [FN-081 §8](../081-creditos-correcao/planejamento.md), que é dona do item | — | **Pendente** |
| **Pendência jurídica 2: questões enviadas ao acervo** | Separar: contexto de autoria; titularidade e licença de uso; autorização de envio ao banco; direito de reutilização, adaptação e distribuição pelo Gabarito360; e a recompensa à pessoa | Licença não exclusiva de uso, adaptação e distribuição, com a autoria contextual preservada ([RN-PRV-16](../../arquitetura/08-regras-de-negocio.md) e [FN-038](../038-disciplinas-curriculo/planejamento.md)). Submeter à revisão jurídica, em especial na questão criada em contexto institucional. Separar o aplicativo não encerra a pendência | **Pendente** |
| **Quantidades e formato comercial dos créditos** (D-3 e D-4) | Ver a [FN-081 §8](../081-creditos-correcao/planejamento.md), que é dona dessas decisões | Decidir junto com os preços dos planos | **Pendente** |
| Autocadastro e SaaS (MP-027, MP-028, MP-029 e RN-GER-09) | Manter planejados ou declarar substituídos | Declarar o MP-028 e o checkout substituídos e o MP-029 obsoleto. Os preços ficam no aplicativo financeiro | **Pendente** |
| Condições de lançamento comercial e regras financeiras de adesão da escola autônoma | — | Tratar com as condições de [09 §2.3](../../arquitetura/09-interface-e-design-system.md). O fato financeiro (assunção paga) vem do aplicativo financeiro; a regra de acesso fica no Gabarito360 | **Pendente** |
| ⚠ Legado ativo além do `MercadoPagoService` | O plano mestre diz "sem código, só o `MercadoPagoService` legado". Estão ativos os itens do §6.3 | Decidir na subfase 3.1, em tarefa própria. Registrar no plano mestre. **Não desligar nesta reorientação** | **Pendente** (divergência entre plano mestre e código) |
| Rotas `assinaturas/*` sem middleware de permissão | Só autenticação; elas filtram por `titular_id` | Rever na subfase 3.1 | **Pendente** |
| Regras ainda não especificadas da escola autônoma | Adesão da instância superior, alcance e vigência da assunção, período já pago e encerramento | Não inventar crédito, prorrata nem vencimento. Levar ao responsável | **Pendente** |
| Bloqueio por inadimplência | A regra antiga (RN-015.4, hoje [RN-GER-09](../../arquitetura/08-regras-de-negocio.md), Substituída) definia bloqueio após 7 dias | Nenhum bloqueio automático sem regra aprovada. `past_due` não implica bloqueio total imediato (histórico de 11/11/2026) | **Pendente** |

| Risco | Efeito | Mitigação |
|---|---|---|
| Recriar o financeiro dentro do Gabarito360 "temporariamente" | Duas verdades comerciais e migração de saldos | Proibição do ADR-0061; verificação de migrations pelo agente de conformidade |
| Simulador ligado em produção ou ativado por falha | Consumo sem autorização e cobrança contestável | Ativação explícita só em desenvolvimento e homologação; testes de falha da API e da configuração de produção (subfase 1.1) |
| Estado fictício confundido com saldo real | Valor de teste tratado como preço, cota ou crédito | Estado separado, identificado como simulação e nunca migrado; valores de teste não definem preços, planos nem cotas |
| Credencial do Gabarito360 com poder administrativo | Um comprometimento alcança o financeiro inteiro | Escopo mínimo da credencial e testes de isolamento (contrato §8) |
| Latência ou queda da API financeira | O modo servidor fica indisponível | Recusa clara, leitura no aparelho e revisão manual continuam; medição da integração (contrato §10) |
| Desligar o legado sem investigação | Perda de dados ou de pagamento em curso | Subfase 3.1 própria, com autorização |

## 9. Ramos desta entrega

Nenhum aberto.

**Próxima entrega:** subfase 1.1, implementação da porta `BillingClient` e do simulador genérico (contrato §9). Ela segue o fluxo do projeto: planejamento aprovado, consulta ao `gerente-projetos` e PR próprio com `FN: FN-068`.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
| 2026-11-11 | Subfase 0.2 de adequação: cabeçalho no modelo atual; citações aos documentos antigos trocadas pelos destinos do mapa; RN-015 passou a RN-GER-09 (Substituída); interface em todas as faixas do ADR-0057; conciliação do legado com o esquema `billing_` do ADR-0061; fronteira com a FN-081 declarada; pendências novas no §8 | lucasgenial |
| 2026-11-12 | Subfase 0.3: reorientação para o aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisão de 12/11/2026). A FN passou a ser dona da experiência limitada, da integração e do legado. F1, F2 e F3 antigas e a conciliação com o esquema `billing_` foram superadas. O contrato passou ao [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md) | lucasgenial |
| 2026-11-12 | Simulador genérico da porta `BillingClient` como primeira entrega da integração (subfase 1.1): processamento educacional real, cenários configuráveis, estado fictício separado e ativação só em desenvolvimento e homologação; piloto em produção pendente | lucasgenial |
| 2026-11-12 | Regra do cliente: exibição temporária de dados autorizados, sem persistir; API real valida a mesma porta contra o contrato antes da troca (subfase 1.2); próxima entrega registrada (subfase 1.1) | lucasgenial |
