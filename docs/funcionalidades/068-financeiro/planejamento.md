# 068 — Plano e uso, integração financeira e legado — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 19
**Posição no plano mestre:** Onda 4 (proposta do §5 do [plano mestre](../../planejamento/00-plano-mestre.md)): 068 financeiro, depois 069 selo; depende de decisão comercial e do aplicativo financeiro externo
**Natureza:** evolução de existente (o legado de assinatura do MP-027 está ativo no código e é documentado a partir dele)
**Origem:** tronco original — pacote financeiro F1–F3 da Fase 5, regras comerciais da escola autônoma e a reconciliação FIN. Os documentos antigos (`docs/28`, `docs/163`, `docs/164`, `docs/176` e `docs/06`) foram removidos; os destinos estão no [mapa dos documentos antigos](../../historico/mapa-documentos-antigos.md). Decisões de billing: [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisado em 12/11/2026. **Reorientação de 12/11/2026:** a FN deixa de ser uma "experiência financeira limitada" e passa a ser dona de Plano e uso, da integração e do legado. Decisão: [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md), que sucede em parte o ADR-0061
**Atualizado em:** 2026-11-12, por lucasgenial

> Escreva este documento no padrão ASD-STE100 moderado (AGENTS.md): uma ideia por frase, voz ativa, frases curtas.

Esta funcionalidade é dona de três assuntos no Gabarito360:

1. **Plano e uso**: a área que mostra, por vínculo, as informações autorizadas recebidas do aplicativo externo, e que oferece entradas contextuais para a Central do cliente;
2. o **módulo de integração** (porta `BillingClient`, cliente da API e simulador de integração);
3. a **situação do legado** de pagamentos do MP-027.

**[Diretriz]** (decisões do responsável de 12/11/2026; o texto de cada uma está no [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)):

- **Plano e uso** mostra, por vínculo: plano vigente ou cobertura institucional, recursos incluídos, ciclo, quantidades, percentuais aplicáveis, disponibilidade, operações em processamento, consumo (mês, ano, intervalo) e extrato. Todos os dados vêm do aplicativo externo. Plano e uso não é um módulo de administração financeira. O Gabarito360 não mantém catálogo comercial, não calcula preços e não é autoridade sobre saldo ou franquia ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2).
- A **Central do cliente** fica no aplicativo externo. Ela atende o professor autônomo e o representante institucional: comprar adicionais, ver opções e mudar de plano, gerir a contratação, gerir a capacidade institucional e acompanhar solicitações. A **administração financeira da empresa** tem permissões e experiência próprias, no mesmo aplicativo externo. "M3 Core" é nome de trabalho, não marca (ADR-0062 §1).
- Modalidades: professor autônomo com plano pessoal; instituição com **uso livre monitorado**; instituição com **capacidade limitada**. A modalidade vem do contrato, nunca da natureza (pública ou privada) da escola (ADR-0062 §3).
- Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos. Uma ação nova usa o vínculo ativo. Uma ação iniciada mantém o contexto e o destinatário originais (ADR-0062 §4).
- No uso livre, o Gabarito360 não cria bloqueio nem indicador de esgotamento por inferência (ADR-0062 §3).
- O valor monetário do plano vigente fica **[Pendente]**. Ele não bloqueia a documentação (ADR-0062 §2).

O financeiro é um aplicativo externo independente ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) e [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)). O contrato da integração é do [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). As regras da correção são da [FN-081](../081-creditos-correcao/planejamento.md). Esta FN não é dona da implementação interna do aplicativo financeiro.

Marcas usadas aqui: **[Diretriz]**, **[Histórico]**, **[Proposto]** e **[Pendente]**, com o significado do [contrato](../../arquitetura/14-integracao-financeira.md) §0.

## 1. Objetivo

Dar ao usuário uma área, Plano e uso, que mostra as informações autorizadas do vínculo ativo, recebidas do aplicativo externo, e que leva às ações da Central do cliente. Dar ao sistema a integração que pede autorização de consumo ao aplicativo externo. Manter o Gabarito360 focado na finalidade educacional e no desempenho.

Plano e uso informa. A Central do cliente age. O Gabarito360 não vira autoridade sobre plano, saldo, franquia ou preço.

## 2. Escopo

- **Inclui:**
  - **Adequação aos princípios** (subfases 0.2, 0.3 e 0.4): revalidação contra os ADRs vigentes, antes de qualquer código.
  - **Porta `BillingClient`** e **cliente da API** do aplicativo financeiro, conforme o contrato. **[Diretriz]**
  - **Simulador genérico da porta `BillingClient`**, primeira entrega da integração, para desenvolvimento e homologação (contrato §9). O processamento educacional é real; só a resposta financeira é simulada. **[Diretriz]**
  - **Referências externas** opacas: conta e operação no aplicativo financeiro. **[Histórico]**
  - **Plano e uso:** consulta, por vínculo, das informações autorizadas recebidas do aplicativo externo (plano vigente ou cobertura institucional, recursos, ciclo, quantidades, percentuais aplicáveis, disponibilidade, operações em processamento, consumo e extrato) e entradas contextuais para a Central do cliente. Sem catálogo, preço calculado ou saldo próprios. **[Diretriz]**
  - **Transição do menu:** o menu-alvo é Plano e uso no menu da conta, como entrada única ao fim da transição. A transição de "Assinatura e cobrança" (legado) é documentada. Nenhuma rota, menu ou código muda nesta etapa. **[Diretriz]**
  - **Situação do legado** do MP-027, sem desligá-lo (§6.3). **[Diretriz]**
  - Regras de produto da escola autônoma que dependem de fato financeiro ([RN-EIN-07 e RN-EIN-08](../../arquitetura/08-regras-de-negocio.md)), sem guardar o fato financeiro no Gabarito360 (§8).
- **Não inclui:**
  - Catálogo, preço, saldo, gestão de limites e alocações, pagamentos, faturas, contratos e administração da empresa → aplicativo financeiro externo, na Central do cliente ou na administração financeira ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md)).
  - Planos, assinaturas, gateways, créditos, bônus, franquias, recompensas, reservas, contabilização, reversões e auditoria financeira → aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) §1).
  - Tela para o perfil interno `financeiro` em Plano e uso. A visão de vários clientes é do aplicativo externo (condição B3).
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

1. **Mockup aprovado:** nenhum. O mockup `mockups/financeiro.*` do PR #275 (SHA `4a10ea2d`, branch `docs/fn-068-mockup-financeiro` preservada) é **proposta superada por mudança de finalidade** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md), Contexto). Ele não está na `main`. O mockup de Plano e uso (subfase 2.3) é obrigatório antes da subfase 2.4. O `mockups/configuracoes.html` (histórico) já trazia "Plano e Uso" no grupo "Conta" (linhas 233-234): serve só como referência de escopo (ADR-0056, item 5).
2. **App atual:** as views, os componentes e o CSS de `apps/web` na `main`. As telas do contexto interno de `clientes/*` (FN-023) e a tela legada `/assinatura` são o ponto de partida.
3. **Documentação:**
   - [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) (Plano e uso e Central do cliente; sucede em parte o ADR-0061), [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) (financeiro externo) e [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md) item 8 (identidade da cobrança da correção).
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
| ADR-0062 Plano e uso e Central do cliente | Reorienta a FN; sucede em parte o ADR-0061 e preserva as regras válidas | Aceita no texto. Passa a valer na `main` só depois da aprovação do responsável e do merge do PR desta subfase |
| Central do cliente (aplicativo externo) | Destino das entradas contextuais de Plano e uso | **Não existe.** Dependência externa. Não bloqueia a documentação nem o mockup |
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
| ~~2.0 Mockup da experiência financeira limitada~~ | Saldo, limites e histórico do contexto; encaminhamento às ações financeiras | — | **Superada (12/11/2026)**, por mudança de finalidade. A experiência financeira limitada foi trocada por Plano e uso (ADR-0062, Contexto). O mockup `mockups/financeiro.*` é proposta superada, não defeito | PR #275 fechado sem merge; SHA `4a10ea2d`; branch `docs/fn-068-mockup-financeiro` preservada |
| ~~2.1 Telas da experiência financeira limitada~~ | Conforme o mockup | — | **Superada (12/11/2026)**, por mudança de finalidade. Nunca iniciada. As telas passam à subfase 2.4 | PR #275 fechado sem merge; SHA `4a10ea2d` |
| 0.4 Adequação aos princípios da versão nova (ADR-0062) | Revalidação da FN-068 contra os princípios da versão que o ADR-0062 gera, depois do script de versão. Fica separada da documentação (decisão do responsável de 12/11/2026) | O campo "Conforme princípios" sobe para a versão vigente; nenhum financeiro local no planejamento | **Entregue (documentação)**. `seguranca-privacidade` fez a revalidação de acesso, dados pessoais e privacidade na segunda leitura. O campo "Conforme princípios" subiu para a versão 19 | Branch `docs/fn-068-plano-e-uso` |
| 2.2 Documentação de Plano e uso | `planejamento.md`, `casos-de-uso.md`, ADR-0062, contrato 14 e documentos donos, com as condições B1 a B6, a lista de 14 estados e o extrato por operação | O responsável aprova os documentos; a revisão de segurança é de outro agente (revisão por outro fornecedor depende da coluna OpenAI do documento 03, hoje vazia); nenhum arquivo de `apps/`, `mockups/` ou migrations muda | **Em andamento** (entrega atual, de 12/11/2026) | — |
| 2.3 Mockup funcional de Plano e uso | Todas as telas, nas 11 larguras (320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px) e nos 2 temas, com viewport real ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)). A matriz evita redundância só nas combinações de perfil e estado. Arquivos: `mockups/plano-e-uso.html`, `mockups/css/plano-e-uso.css` e `mockups/js/plano-e-uso.js` | O responsável aprova o mockup. A revisão do `ui-ux` ocorre no navegador, sem gravar prints no histórico (ADR-0049). A revisão de segurança é de outro agente | **Pendente** (**não autorizada**). O executor, `desenvolvedor-web` restrito aos três arquivos acima, depende de autorização escrita do responsável | — |
| 2.4 Telas de Plano e uso | Conforme o mockup aprovado e os casos de uso | O usuário vê só o próprio vínculo; conferência por texto em todas as larguras de referência | **Pendente** (depende da 2.3 e do planejamento de implementação) | — |
| 3.1 Destino do legado do MP-027 | Investigação e proposta para checkout, assinaturas, webhook, dados e tabelas. O destino está **pendente, a investigar e decidir em tarefa própria** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §7). Os achados de segurança do Mercado Pago só são registrados (§8) | O responsável autoriza o destino em tarefa própria. Nada é desligado antes | Pendente | — |
| ~~1.1 (F1 antiga) Modelo comercial e fiscal~~ | Contratos, faturas, cobranças, pagamentos, notas fiscais, catálogo e assinaturas no banco do Gabarito360 | — | **Superada (12/11/2026).** Direção reservada ao aplicativo financeiro (ADR-0061 §1). Não confundir com a 1.1 viva (porta e simulador) | — |
| ~~2.0/2.1 (F2 antiga) Telas do Financeiro~~ | Contratos, faturas, baixa manual, inadimplência e painel de recebimentos | — | **Superada (12/11/2026).** Ver §8, item "contratos, faturas e notas fiscais". Não confundir com a 2.0 e a 2.1 acima, também superadas, por outro motivo | — |
| ~~3.1 (F3 antiga) Interface de pagamentos~~ | `GatewayPagamento`, `GatewayManual`, adaptação do Mercado Pago e webhook por provedor | — | **Superada (12/11/2026).** Meios de pagamento ficam no aplicativo financeiro. Não confundir com a 3.1 viva (destino do legado) | — |

**Nota sobre a numeração.** Os números 1.1, 2.0/2.1 e 3.1 das linhas riscadas "F1/F2/F3 antiga" repetem os números de subfases vivas ou superadas depois. Elas não foram renumeradas, para preservar o histórico. As siglas "antiga" distinguem os casos. As subfases novas 0.4 e 2.2 a 2.4 não reutilizam número.

**Classe e modelo por papel (subfase 2.2, documentação).** Regra 7 do [02-agentes](../../planejamento/02-agentes.md) §4.3. Fonte: parecer do `selecao-modelos` no levantamento de 12/11/2026. O [03-modelos-de-ia](../../planejamento/03-modelos-de-ia.md) é PROPOSTA ainda não aprovada. A coluna OpenAI está vazia nele. Por isso, nenhuma revisão desta subfase é registrada como independente de fornecedor. Os modelos da tabela foram confirmados como os usados nesta subfase.

| Papel | Agente | Classe | Modelo (Anthropic) | Motivo |
|---|---|---|---|---|
| Coordenação | `gerente-projetos` | equilibrado | Claude Sonnet 5.5 | Padrão. Consolida; não decide arquitetura |
| Jornadas e casos de uso | `product-design` | equilibrado | Claude Sonnet 5.5 | Padrão |
| Fronteira e ADR | `arquiteto-software` | raciocínio-alto | Claude Opus 5.5 | Padrão. Decisão durável |
| Indicadores e extrato | `dados-dashboards` | **raciocínio-alto** | Claude Opus 5.5 | **Subiu** do padrão equilibrado: define permissão de extrato por vínculo |
| Documentos donos | `documentacao` | equilibrado | Claude Sonnet 5.5 | Padrão |
| Propagação do ADR | `impacto-principios` | **equilibrado** | Claude Sonnet 5.5 | **Subiu** do padrão economico: a sucessão parcial do ADR-0061 exige julgamento |
| Revisão de segurança | `seguranca-privacidade` | raciocínio-alto | Claude Opus 5.5 | Padrão. Permissões, vínculos e dados pessoais usam sempre raciocínio-alto |
| Revisão de qualidade | `qualidade-testes` | equilibrado | Claude Sonnet 5.5 | Padrão |
| Consulta de interface | `ui-ux` | equilibrado | Claude Sonnet 5.5 | Padrão. Na subfase 2.3 (mockup), sobe a raciocínio-alto na troca de vínculo |

⚠ Lacuna de escopo: nenhum agente do [02-agentes](../../planejamento/02-agentes.md) §3 tem `mockups/**` como área editável. A subfase 2.3 exige autorização escrita do responsável para o `desenvolvedor-web`, restrita aos três arquivos dela.

**Pareceres da subfase 2.2.** A revisão é independente de agente, mas **não de fornecedor**: a coluna OpenAI do [03-modelos-de-ia](../../planejamento/03-modelos-de-ia.md) está vazia.

| Agente | Classe e modelo | Veredito | Data |
|---|---|---|---|
| `seguranca-privacidade` | raciocínio-alto, Claude Opus 5.5 | Reprovado até três correções de texto; depois aprovado com ressalvas | 2026-11-12 |
| `qualidade-testes` | equilibrado, Claude Sonnet 5.5 | Aprovado com ressalvas | 2026-11-12 |
| `ui-ux` | equilibrado, Claude Sonnet 5.5 | Consulta prévia aprovada com ressalvas | 2026-11-12 |
| `seguranca-privacidade` (segunda leitura) | raciocínio-alto, Claude Opus 5.5 | Aprovado com ressalvas. Condições B1 a B6 atendidas. Revalidação de acesso, dados pessoais e privacidade: "Pode subir", na FN-068 e na FN-081 | 2026-11-12 |
| `qualidade-testes` (segunda leitura) | equilibrado, Claude Sonnet 5.5 | Aprovado com ressalvas. Os meios de prova da UC-081-09 e a forma do contrato 14 §4 foram corrigidos | 2026-11-12 |
| `ui-ux` (segunda leitura) | equilibrado, Claude Sonnet 5.5 | Aprovado com ressalvas. Os requisitos de `x-carregando`, `x-vazio` e abas para o mockup estão no §7 | 2026-11-12 |

A independência das segundas leituras é de agente, não de fornecedor. As ressalvas da segurança que continuam, sem impedir a subida do campo: (1) [doc 11](../../arquitetura/11-seguranca-e-privacidade.md) e LGPD, em tarefa própria; (2) log do nginx de `deploy/` (§8); (3) a frase sobre a finalidade do consumo por professor é recomendação, não regra (§8).

A revisão do levantamento de 12/11/2026 sobre o mockup anterior (PR #275) concluiu: "não representa Plano e uso (finalidade anterior)".

## 6. Contrato técnico (proporcional ao risco)

### 6.1 Integração

O contrato é o [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md). Esta seção não o copia. As decisões são o [ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md) e o [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md), que o sucede em parte. Nada desta seção está implementado: o código não tem a porta, o simulador, a consulta de Plano e uso nem a travessia (busca por `BillingClient` em `apps/`: nenhum resultado).

- A porta `BillingClient` é a única passagem para o financeiro. **[Diretriz]**
- **Fluxo de dados.** A Web chama a API do Gabarito360 por HTTP. A API resolve o vínculo e o contexto de cobrança e chama a porta. A Web nunca fala com o aplicativo externo nem com a Central do cliente por chamada de servidor. **[Proposto]**
- **Plano e uso** usa as consultas do contrato §3.2: cobertura do vínculo, disponibilidade por recurso, consumo agregado, extrato e contratações iniciadas. Endpoints e formatos não estão definidos. **[Proposto]**
- **Entradas para a Central do cliente** usam a travessia do contrato §13. **[Proposto]**; mecanismo **[Pendente]**.
- O Gabarito360 guarda só referências externas opacas, a chave das operações e o último resultado conhecido de cada operação (contrato §6). A intenção de travessia, se ficar no Gabarito360, é registro operacional sem valor financeiro (contrato §13.1). **[Proposto]**
- Nenhuma migration desta FN cria carteira, ledger, lotes, saldo, preço, catálogo ou esquema financeiro. **[Diretriz]**
- Nenhuma tela, rota ou formulário desta FN edita limite, alocação, capacidade, saldo ou plano. Nenhum código calcula preço. **[Diretriz]** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md))
- A credencial técnica do Gabarito360 fica no servidor, fora do repositório, com escopo limitado às capacidades do contrato §3. Ela não administra o financeiro. **[Diretriz]**
- A indisponibilidade da API financeira nunca ativa o simulador nem libera consumo. **[Diretriz]**
- O simulador só funciona com ativação explícita no servidor, em desenvolvimento e homologação. O estado dele é fictício, separado das tabelas do produto, reiniciável e sem valor comercial. Ele nunca é migrado para o aplicativo financeiro (contrato §9.3 e §9.4). **[Diretriz]**

### 6.2 Autorização e dados

| Assunto | Regra | Marca | Dono |
|---|---|---|---|
| Consulta por vínculo | Plano e uso mostra só o vínculo ativo: pessoal, escola ou núcleo. A API deriva o vínculo e o contexto de cobrança da sessão, no servidor. Nenhum identificador vindo do navegador escolhe vínculo, contexto ou conta contratante. A consulta não muda a titularidade contratual ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §4; [ADR-0024](../../decisoes/0024-vinculo-ativo-acompanha-a-sessao.md)) | [Diretriz] | Contrato §3.2, item 1 |
| Ação iniciada | Uma ação nova usa o vínculo ativo. Uma ação iniciada mantém pessoa, vínculo, contexto e destinatário originais, mesmo depois de uma troca de vínculo. A tela mostra o destinatário antes da saída. Um retorno com outro vínculo ativo mostra só um aviso neutro (ADR-0062 §4; condição B5) | [Diretriz] (regra); [Proposto] (controles) | Contrato §13.1 e §13.2 |
| Retorno da Central | Lista fechada de retorno por ambiente; só correlação opaca na URL; voltar do checkout não prova pagamento; confirmação externa autenticada, correlacionada e idempotente; nenhum segredo em URL, log ou `Referer`; emissão auditada sem token (condição B6) | [Proposto] | Contrato §13.2 a §13.4 |
| Papel financeiro | O aplicativo externo confere identidade, papel e contrato em cada ação humana. Nenhum papel financeiro é deduzido de perfil pedagógico ou de direção. Esta FN não cria chave de permissão (condição B2) | [Diretriz] | Contrato §8, itens 3 e 6; [04](../../arquitetura/04-identidade-acesso-e-permissoes.md) §9.1 |
| Representante institucional | Quem vê o resumo institucional e quem abre a Central pela instituição: sem definição. As jornadas que dependem disso ficam "não verificáveis até a decisão" | [Pendente] | §8 desta FN |
| Perfil interno `financeiro` | Não tem tela em Plano e uso. A visão de vários clientes é da administração financeira da empresa, no aplicativo externo. Nenhum código soma registros de vários clientes. Hoje o perfil tem só `cliente.ver` e `cliente.gerir` (`apps/api/app/Support/ContextoInterno.php:15`). O papel futuro é pendente (condição B3) | [Diretriz] (sem tela); [Pendente] (papel futuro) | Contrato §8, item 7; §8 desta FN |
| Detalhe pedagógico | O aplicativo externo fornece cobertura, disponibilidade, consumo, unidade, situação e hora da atualização. Ele não recebe nem devolve dado pedagógico. A linha de consumo traz a referência `correction:{correction_subject_id}`. O Gabarito360 resolve aluno e aplicação no próprio banco, no momento do pedido, por `Escopo` e por permissão existente com proteção demonstrada. Sem a demonstração, ou na dúvida, o detalhe mostra "Dado restrito" (condição B4) | [Diretriz] (regra); [Pendente] (chave exata) | Contrato §5; [FN-081](../081-creditos-correcao/planejamento.md) §6.5, item 19 |
| Uso livre | Nenhum bloqueio, indicador de esgotamento ou percentual por inferência sobre a referência de acompanhamento (ADR-0062 §3) | [Diretriz] | Contrato §3.2, item 4, e §4, item 8 |
| Cliente | O navegador ou o aparelho só exibe, de forma temporária, os dados autorizados. Ele não os persiste, não guarda cache e não recebe credenciais técnicas ou administrativas | [Diretriz] | Contrato §2 |
| Cache na API | Sem cache, ou cache só na API com chave pessoa + vínculo + contexto, prazo curto e invalidação por notificação. Decisão no planejamento de implementação, com medição de 1.000 usuários ([ADR-0052](../../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)) | [Pendente] | Contrato §3.2 |
| Volume | Meta de 100 instituições. O extrato tem ordem estável e paginação. O tamanho da página e o intervalo máximo vêm do contrato externo | [Proposto]; [Pendente] (paginação) | Contrato §3.2, item 7 |

**Testes que a implementação deve ter** (subfase 2.4; nenhum existe hoje) **[Proposto]**:

1. Isolamento por vínculo: a mesma pessoa, com dois vínculos, nunca vê dados de um no outro.
2. Uma ação iniciada mantém o destinatário original depois de uma troca de vínculo.
3. Um identificador de vínculo, contexto ou conta enviado pelo navegador é ignorado ou recusado.
4. Sem a permissão demonstrada, a resposta da API traz "Dado restrito", não só a tela.
5. O retorno do checkout não muda o estado da contratação.
6. O perfil interno `financeiro` não alcança Plano e uso.
7. No uso livre, nenhuma resposta tem percentual nem indicador de esgotamento sobre a referência.
8. Um destino de retorno fora da lista fechada do ambiente é recusado. Um parâmetro livre de URL não muda o destino (contrato §13.2).
9. A mesma confirmação externa, recebida duas vezes, produz um só efeito (contrato §13.3).
10. Com a API financeira indisponível, a resposta é o estado 5. O simulador não é ativado, e nenhum valor antigo aparece como atual (contrato §3.2, item 6; §9.4, item 4; §10, item 6).
11. A credencial técnica do Gabarito360 não compra, não muda plano e não altera limite, capacidade ou alocação. O aplicativo externo recusa essas ações com ela (contrato §8).
12. Nenhuma carga enviada ao aplicativo externo, nem recebida dele, contém nome de aluno, resposta, nota ou outro dado pedagógico (contrato §5).
13. Um perfil sem permissão para Plano e uso recebe a negação na resposta da API, não só na tela. A chave que dá a permissão é **[Pendente]** (§6.4.1, item 5).

### 6.3 Situação do legado

O código atual tem o legado do MP-027. Esta FN só documenta. **Nada é removido ou desligado nesta reorientação.** **[Diretriz]** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §7)

**Destino: pendente, a investigar e decidir em tarefa própria (subfase 3.1).**

Os caminhos abaixo são relativos a `apps/` e foram conferidos na worktree em 12/11/2026. O inventário completo, com a observação de cada item, é do [contrato](../../arquitetura/14-integracao-financeira.md) §11. Os itens marcados com ⚠ são **achados de leitura de código, não comprovados em produção**. Os valores de configuração da homologação e da produção não foram conferidos.

| Item legado | Onde está | Situação |
|---|---|---|
| Tabelas `planos`, `assinaturas` e `pagamentos` | `api/database/migrations/2026_06_19_000001` a `000003` | Ativas; valores em `decimal(10,2)`; titular por conta de acesso (`…000002…:14`), limitação do legado (condição B1) |
| `GET /planos` | `api/routes/api.php:37` | Rota pública ativa |
| `POST /assinaturas/checkout` | `api/routes/api.php:335`; `AssinaturaController.php:14-18` | Devolve 409 (contratação assistida) |
| ⚠ `GET /assinaturas/minha` e `POST /assinaturas/cancelar` | `api/routes/api.php:333-338` | Ativas; autenticação e vínculo, sem middleware de permissão; filtram por `titular_id` (`AssinaturaController.php:26` e `:43`); `cancelar` chama o gateway (`:47-48`) |
| `POST /webhooks/mercadopago` | `api/routes/api.php:34`; `WebhookController::mercadoPago` | Rota pública ativa, com HMAC |
| ⚠ Validação do webhook com segredo vazio | `api/app/Services/MercadoPagoService.php:135-137`; padrão `''` em `api/config/mercadopago.php:6` | Aceita qualquer chamada quando o segredo está vazio |
| ⚠ Texto assinado do webhook sem o corpo | `MercadoPagoService.php:151-152` | A conferir contra o formato do provedor |
| ⚠ Pré-aprovação com status do corpo | `api/app/Http/Controllers/Api/WebhookController.php:123` e `:130-131` | Cancela a assinatura sem consultar o gateway |
| ⚠ `VerificarInadimplenciaJob` | `api/routes/console.php:12` (diário, 03:00) | Agendado; muda assinaturas para `atrasada` (`VerificarInadimplenciaJob.php:29` e `:40`) |
| ⚠ Middleware `assinatura_ativa` | `api/bootstrap/app.php:25`; `CheckAssinaturaAtiva.php:25` e `:56` | Registrado e sem uso em rotas; usa `escopos()` antigo; responde 402 |
| ⚠ Retorno do checkout | `web/routes/web.php:43-46`; `web/app/Http/Controllers/CheckoutController.php:26`; `web/resources/views/checkout/sucesso.blade.php` | Rota pública; lê `status` da URL (padrão `approved`); a página afirma "Pagamento confirmado!" sem consultar nada |
| Web `/checkout` e `/assinatura` | `web/routes/web.php:40-41` e `:308-309` | `/checkout` redireciona ao Fale Conosco |
| Menu "Assinatura e cobrança" | `web/resources/views/layouts/app.blade.php:46` | Aparece para `auth_titular` fora do contexto interno; ponto da transição para Plano e uso (§2) |

Regras:

1. O legado não é o novo financeiro. Ele não vira carteira nem fonte de saldo.
2. O legado não é modelo para Plano e uso. Em especial, o retorno do checkout é o contraexemplo da regra "voltar do checkout não prova pagamento" (contrato §13.2).
3. O destino do legado (manter, migrar para o aplicativo financeiro, desativar ou outro) é decidido na subfase 3.1, com investigação e autorização próprias. A investigação não expõe segredos.
4. Os dados legados são preservados até essa decisão. Nada é desligado antes.

### 6.4 Indicadores e extrato de Plano e uso

Esta seção define o que Plano e uso mostra, de onde vem cada dado e como a tela trata a falta dele. O contrato da consulta é do [14](../../arquitetura/14-integracao-financeira.md) §3.2. As regras de consumo da correção são da [FN-081](../081-creditos-correcao/planejamento.md) §6. Os estados citados por número são os da lista única dos [casos de uso](casos-de-uso.md) (seção "Estados"). **Nada desta seção está implementado.** Nenhuma rota, tabela ou endpoint é escolhido aqui.

#### 6.4.1 Regras comuns

1. **Fonte única.** Todo indicador vem do aplicativo externo, pela porta `BillingClient` (contrato §3.2). O aplicativo externo é a autoridade. O registro operacional do Gabarito360 (contrato §6, item 10; FN-081 §6.5, item 17) serve só para correlacionar a linha com a operação. Ele nunca responde "quanto resta" nem "quanto foi consumido". Uma consulta que soma quantidades locais para obter saldo ou consumo cria um ledger e é proibida (ADR-0061). **[Diretriz]**
2. **Universo.** Um vínculo ativo da sessão e um recurso por vez. A API resolve o vínculo no servidor (§6.2, "Consulta por vínculo"). A consulta não soma vínculos, contextos de cobrança nem clientes. **[Diretriz]**
3. **Nenhum cálculo local.** O Gabarito360 não subtrai, não soma e não deduz percentual. Ele mostra os valores que o aplicativo externo devolve. **[Diretriz]** (ADR-0062 §2)
4. **Hora e origem.** Cada bloco mostra a hora em que o aplicativo externo produziu o dado e a origem ("informado pela Central do cliente" ou texto equivalente aprovado no mockup). **[Proposto]** (texto); **[Diretriz]** (regra, contrato §3.2, item 5)
5. **Permissão para abrir Plano e uso.** Nenhuma chave do [04](../../arquitetura/04-identidade-acesso-e-permissoes.md) cobre a área. O catálogo de permissões do código não tem chave financeira (`apps/api/app/Support/CatalogoPermissoes.php`). Esta FN não cria chave (condição B2). Quem vê a área em cada vínculo, e quem vê o resumo institucional, fica **[Pendente]** (§8). O perfil interno `financeiro` não tem tela (condição B3).

#### 6.4.2 Tabela de indicadores

| Indicador | Significado | Unidade | Fonte | Período | Universo | Permissão | Ausente / zero / indisponível / sem cobertura |
|---|---|---|---|---|---|---|---|
| Plano vigente ou cobertura | Nome do plano pessoal ou texto da cobertura institucional; modalidade efetiva (pessoal, uso livre monitorado, capacidade limitada); recursos incluídos; ciclo vigente; quantidades autorizadas por recurso | Texto; quantidades na unidade de cada recurso | Externa: "Consultar cobertura do vínculo" (contrato §3.2) | Ciclo vigente | Vínculo ativo | **[Pendente]** (6.4.1, item 5) | Ausente: não existe "plano gratuito" fictício. Sem cobertura: estado 6. Indisponível: estado 5, sem último valor. Zero não se aplica ao texto |
| Valor monetário do plano | Valor do plano vigente | — | — | — | — | — | **[Pendente]** (ADR-0062 §2). Não é proibido nem exigido. Sem decisão, a tela não mostra valor |
| Disponibilidade por recurso | Para cada recurso: capacidade disponível, franquia do ciclo, adicionais e teto de uso do período, quando houver. Cada um em campo próprio | Unidade do recurso (contrato §3.2, item 2) | Externa: "Consultar disponibilidade por recurso" | Ciclo vigente (instantâneo) | Vínculo ativo × recurso | **[Pendente]**. O teto individual do professor não expõe o saldo da instituição a quem não pode vê-lo (contrato §7) | Ausente: o campo não aparece. Não vira "0". Zero real: "0" com unidade e hora. Sem capacidade para a operação: estado 7. Teto atingido: estado 8 (nunca no uso livre sem decisão). Indisponível: estado 5 |
| Operações em processamento | Quantidade de operações com reserva aberta. Informa. Não subtrai nada na tela | Unidade do recurso | Externa: disponibilidade por recurso. O registro operacional só correlaciona | Instantâneo | Vínculo ativo × recurso | **[Pendente]** | Ausente ou zero: "Nenhuma operação em processamento". Indisponível: estado 5 |
| Consumo no período | Quantidade confirmada no período. Reserva, liberação e reenvio não entram | Unidade do recurso | Externa: "Consultar consumo agregado". O total vem pronto do aplicativo externo | Mês, ano ou intervalo | Vínculo ativo × recurso | **[Pendente]** | Período sem uso: estado 2 ("Sem uso neste período"), não um "0" de indicador. Zero confirmado pelo aplicativo externo: "0" com hora. Indisponível: estado 5 |
| Percentual de uso | Consumo sobre a quantidade contratada | %, como devolvido pelo aplicativo externo. O Gabarito360 não calcula nem arredonda. A precisão é **[Pendente]** no contrato externo | Externa. O aplicativo externo informa se a quantidade admite percentual (contrato §3.2, item 4) | Ciclo vigente | Vínculo ativo × recurso, só onde há quantidade contratada | **[Pendente]** | Não se aplica: o campo não aparece. Nunca aparece sobre a referência de uso livre (ADR-0062 §3). Indisponível: estado 5 |
| Referência de uso livre | Valor de acompanhamento contratual, ao lado do consumo. Não é saldo, teto, cota nem dívida | Unidade do recurso | Externa: disponibilidade por recurso, no uso livre monitorado | Período informado pelo aplicativo externo | Vínculo institucional × recurso | **[Pendente]** | Sem percentual, sem barra até 100%, sem cor de alerta e sem rótulo de esgotamento, excesso ou dívida (ADR-0062 §3; lista de termos proibidos dos casos de uso). Consumo acima da referência: só os dois números, lado a lado. O comportamento além da referência é **[Pendente]** |
| Hora da atualização | Momento em que o aplicativo externo produziu o dado | Data e hora. O fuso de exibição segue a regra geral do app | Externa (contrato §3.2, item 5) | — | Cada resposta | A mesma do bloco que ela acompanha | Sem hora: o bloco é tratado como indisponível (estado 5) |
| Contratações iniciadas | Situação de cada intenção de travessia do vínculo | Situação (texto) | Externa: "Consultar contratações iniciadas" | Em aberto e recentes | Vínculo de origem da intenção (ADR-0062 §4) | **[Pendente]** | Estados 9 a 14. Retorno com outro vínculo ativo: só aviso neutro (§6.2) |

Os números do mockup futuro são de demonstração (CC-5 dos casos de uso). Nenhum número desta seção é regra comercial. **[Diretriz]**

#### 6.4.3 Regras de unidade **[Proposto]**, sobre a regra do contrato §3.2, itens 2 e 3 **[Diretriz]**

1. **Um bloco por recurso.** Cada bloco tem o nome do recurso e a unidade na mesma linha de cada valor. O único recurso documentado hoje é `correction.server.student` (FN-081 §6.1, item 1). A lista de recursos é **[Pendente]** (D-3).
2. **Nunca somar unidades.** Recursos diferentes nunca formam um total. No mesmo recurso, franquia, adicionais, teto e operações em processamento ficam em linhas separadas. Não existe "Saldo total".
3. **Teto, não saldo.** O limite aparece como "teto de uso do período". Ele não aparece como "restante", "disponível" nem "saldo". O Gabarito360 não calcula "teto − consumo".
4. **Franquia por recurso não é carteira comum.** A tela não junta franquia e adicionais num único número (ADR-0061; FN-081 D-5).
5. **Benefícios em linha própria.** Bônus e Créditos por contribuição validada aparecem com a origem, sem somar à franquia (FN-081 §6.6).

#### 6.4.4 Extrato **[Proposto]**

Modelo reconciliado no levantamento de 12/11/2026. Os termos de reserva, confirmação e liberação são do contrato §3.1 e §6 e da FN-081 §6.2 a §6.4.

| Linha | Situação mostrada | Sinal | Conta no consumo do período? |
|---|---|---|---|
| Operação de consumo, chave `correction:{correction_subject_id}` | "Em processamento" (reserva), "Confirmado" ou "Não cobrado" (liberação). A mesma linha muda de situação | Sem sinal. Quantidade na unidade do recurso | Só na situação "Confirmado", uma vez por operação |
| Reenvio do mesmo sujeito | Evento informativo "Reenvio sem nova cobrança", ligado à mesma operação (FN-081 §6.1, item 5; §6.4, item 16) | Sem quantidade | Não |
| Benefício ou crédito recebido | Evento com a origem: bônus ou Créditos por contribuição validada (`GRANT`, origem `REWARD`; FN-081 §6.6, item 22) | Sem sinal de débito | Não |
| Alteração de limite | Evento "Teto de uso alterado". Não é consumo nem compra | Sem quantidade de consumo | Não. A origem do evento é **[Pendente]** no contrato (§7) |

Regras do extrato:

1. **Uma operação, uma linha.** Reserva seguida de confirmação nunca aparece como dois débitos. A linha mostra a situação atual. **[Proposto]** (modelo do extrato), sobre a regra **[Histórico]** de no máximo um consumo definitivo por operação (contrato §6, item 3; FN-081 §6.1, item 4)
2. **Compra, fatura e pagamento ficam fora.** Eles estão na Central do cliente (ADR-0062 §1). **[Diretriz]**
3. **Sem saldo corrente.** O extrato não tem coluna de saldo acumulado. **[Proposto]**
4. **Ordem estável:** mais recente primeiro, com desempate pela referência opaca. **[Proposto]**
5. **Filtros:** período (mês, ano ou intervalo), recurso e situação. Um filtro por correção vem da tela de correção (UC-068-27). **[Proposto]**
6. **Estados:** sem linhas no período, estado 2; falha, estado 5. A tela não mostra a última página carregada como atual. **[Proposto]**

#### 6.4.5 Detalhe pedagógico da linha (condição B4)

**[Diretriz]** O aplicativo externo não recebe nem devolve nome de aluno, resposta, nota ou outro detalhe pedagógico. A linha traz só a referência opaca da operação (contrato §5). O Gabarito360 resolve aluno e aplicação no próprio banco, no momento do pedido, pelo `Escopo` do vínculo (`apps/api/app/Support/Escopo.php`) e por uma permissão **existente cuja proteção seja demonstrada**. Sem a demonstração, ou na dúvida, o campo mostra "Dado restrito". A linha de consumo continua visível. A regra dona é a da [FN-081](../081-creditos-correcao/planejamento.md) §6.5, item 19. Acesso à prova sozinho não basta: o Aplicador tem `prova.ver` (04 §6.4) e essa chave não prova acesso ao aluno. Esta seção só cita a regra e não a repete.

| Campo | Com a permissão demonstrada | Sem a demonstração ou na dúvida |
|---|---|---|
| Aluno | Nome | "Dado restrito" |
| Aplicação | Identificação da aplicação e turma | "Dado restrito" |
| Referência da operação | Código opaco | Código opaco |
| Quantidade, unidade, situação e hora | Sim | Sim |

**Chave exata: [Pendente].** Nenhuma chave é escolhida por "parecer restritiva". Chaves existentes candidatas, conferidas no 04 §6.3 a §6.6 e em `CatalogoPermissoes.php` (linhas 79, 84, 97 e na lista padrão das linhas 23 a 26):

| Candidata | Por que ainda não está demonstrada |
|---|---|
| `aluno.ver` | O perfil Aluno tem a chave só para o próprio cadastro (04 §6.3, nota 2). A demonstração precisa provar que o `Escopo` corta o detalhe de outros alunos |
| `prova.ver` | O Aplicador tem a chave só para a identificação das provas do dia (04 §6.4). Ela não prova acesso ao aluno |
| `correcao.acompanhar` | Cobre a correção da prova e da turma (04 §6.5). Não se sabe se cobre todas as operações de um extrato institucional |
| `resultado.ver` | Mostra acertos e erros (04 §6.6). Ela dá mais do que o detalhe pede |

**Critério da demonstração [Proposto].** A chave (ou a combinação) só vale depois de três provas, revisadas pelo `seguranca-privacidade`:

1. Teste automatizado: para cada perfil do 04, com e sem a chave, a resposta da API traz o nome só quando o aluno e a aplicação estão no `Escopo` do vínculo ativo.
2. Teste automatizado: Aplicador, Aluno e o perfil interno `financeiro` recebem "Dado restrito" na resposta da API, não só na tela.
3. Revisão documental: a decisão cita a chave, o perfil e o teste. Até lá, toda resposta mostra "Dado restrito".

A entidade de aplicação e o formato do `correction_subject_id` são **[Pendente]** na FN-081 §8. Sem eles, o detalhe não resolve aluno e aplicação.

#### 6.4.6 Contrato de dados da API do Gabarito360 para a Web **[Proposto]**

Os nomes são provisórios. Eles descrevem o conteúdo, não o endpoint. O formato final sai do planejamento de implementação.

- **Resumo:** `vinculo` (rótulo para exibição, sem identificador que selecione contexto), `modalidade`, `cobertura` (texto), `ciclo`, `atualizado_em`, `origem`, `estado` (sucesso, sem_cobertura, negado, indisponivel).
- **Por recurso (`recursos[]`):** `recurso`, `unidade`, `disponivel`, `franquia_ciclo`, `adicionais`, `teto_periodo`, `em_processamento`, `percentual` (só quando o aplicativo externo o informa), `referencia_acompanhamento` (só no uso livre). Campo sem valor vem ausente, nunca zero.
- **Consumo:** `recurso`, `unidade`, `periodo` (mês, ano ou intervalo), `confirmado`, `atualizado_em`.
- **Extrato (`itens[]`):** `operacao` (referência opaca), `tipo` (consumo, reenvio, beneficio, alteracao_teto), `situacao` (em_processamento, confirmado, nao_cobrado; vazio nos eventos), `recurso`, `quantidade`, `unidade`, `origem` (nos benefícios), `ocorrido_em`, `detalhe` (`{aluno, aplicacao}` ou `{restrito: true}`).
- **Paginação:** `proximo_cursor`, `tamanho_pagina`, `intervalo_maximo`, conforme o contrato externo.
- O navegador não persiste a resposta (§6.2, "Cliente").

#### 6.4.7 Volume e desempenho

1. **Paginação e intervalo máximo.** O extrato tem paginação por cursor e intervalo máximo por consulta. **[Proposto]** como requisito ao contrato externo. Os valores são **[Pendente]** no contrato 14 §3.2, item 7. Esta FN não fixa números.
2. **Totais prontos.** O consumo agregado vem pronto do aplicativo externo. O Gabarito360 não percorre páginas para somar. **[Proposto]**
3. **Detalhe em lote.** O Gabarito360 resolve aluno e aplicação com uma consulta em lote por página, sem uma consulta por linha (UC-068-27). **[Proposto]**
4. **Medição.** As consultas de Plano e uso são medidas com 1.000 usuários simultâneos na implementação ([ADR-0052](../../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md); [12 Desempenho](../../arquitetura/12-desempenho.md)). A meta inicial é de 100 instituições, 600 professores e 1.000 responsáveis (12 Desempenho, seção "Padrão de carga"). **[Diretriz]** (medição)
5. **Agente `desempenho`.** Ele entra no planejamento de implementação, junto da decisão de cache (§6.2, "Cache na API"). Ele não bloqueia a documentação nem o mockup. **[Proposto]**
6. **Riscos.** Extrato institucional de intervalo longo; latência do aplicativo externo; consulta por linha no detalhe pedagógico. A mitigação são os itens 1 a 3.

#### 6.4.8 Gráficos e alternativa acessível **[Proposto]**

1. **Visão geral:** cartões de texto, um por recurso. Sem gráfico para franquia, teto ou disponibilidade.
2. **Consumo ao longo do tempo:** barras simples por período (mês), um recurso por vez. O eixo mostra a unidade.
3. **Percentual:** barra de progresso só onde o aplicativo externo informa o percentual (6.4.2).
4. **Uso livre:** consumo e referência como dois números, lado a lado. No gráfico de barras, a referência pode ser uma linha neutra rotulada "referência de acompanhamento". Sem cor de alerta, sem barra até 100% e sem rótulo "excedido".
5. **Proibido:** pizza entre recursos; barras empilhadas de unidades diferentes; qualquer gráfico que some recursos.
6. **Alternativa acessível:** a mesma série em tabela, com título, unidade e uma frase-resumo em texto. O gráfico nunca é a única fonte do dado. A cor nunca é o único sinal.

## 7. Interface

O app é responsivo ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)). As telas desta FN atendem a **todas as faixas**: celular pequeno, celular, tablet (retrato e paisagem), notebook, desktop e telas grandes até 4K, com regras próprias para toque.

- Confira cada tela nas larguras 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro.
- Use os cortes canônicos do ADR-0057, `pointer: coarse` e consultas de contêiner. Um corte novo exige registro no design system.
- A referência visual é o app atual ([ADR-0056](../../decisoes/0056-app-atual-como-referencia-visual.md)). O design system é `style-system/`.
- A tela mostra as informações autorizadas do vínculo ativo, recebidas do aplicativo externo: cobertura, disponibilidade, consumo e extrato. Ela informa a origem do dado e a hora da atualização. **[Proposto]**
- Sem resposta do aplicativo externo, a tela mostra o estado 5. Ela não mostra valor calculado no Gabarito360.
- As tabelas seguem a [FN-074](../074-tabelas-padrao/planejamento.md), com a coluna Ações à direita ([ADR-0039](../../decisoes/0039-coluna-de-acoes-a-direita-e-ordenacao.md)). O app já tem o componente `x-tabela` (`apps/web/resources/views/components/tabela.blade.php`).
- Estados obrigatórios em cada tela: carregando, vazio e erro, além dos demais da lista única (ver 7.3).
- Confirmações usam modal da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)). O JavaScript fica em arquivo externo ([ADR-0023](../../decisoes/0023-javascript-em-arquivos-externos.md)).
- **O mockup vem antes das telas** (subfase 2.3). A revisão de UI/UX é de outro agente ([ADR-0020](../../decisoes/0020-revisao-de-ui-ux-por-especialista.md)).
- A conferência é registrada por texto, sem prints ([ADR-0049](../../decisoes/0049-sem-prints-da-aplicacao.md)).
- Nenhuma tela, rota ou menu existe hoje para Plano e uso. Tudo abaixo é **[Proposto]**, salvo onde houver a marca **[Diretriz]**. Nenhuma tela nova é criada fora de mockup aprovado.

### 7.1 Arquitetura de informação **[Proposto]**

1. **Uma rota**, a definir na subfase 2.4. Ela mostra o vínculo ativo em destaque (CC-1) e duas abas: **Resumo** e **Extrato**. O vínculo vem da sessão. Nenhum identificador do navegador escolhe vínculo ou contexto (§6.2).
2. **Resumo:** cobertura e modalidade, ciclo, um cartão por recurso (§6.4.3), operações em processamento, consumo do período, benefícios recebidos e contratações iniciadas. Entradas contextuais para a Central do cliente ficam no próprio cartão do recurso, não numa tela de compra local.
3. **Extrato:** tabela por operação (`x-tabela`), com filtros de período, recurso e situação (§6.4.4) em modal, no padrão da [FN-074](../074-tabelas-padrao/planejamento.md) e do doc 09. O detalhe de aluno e aplicação mostra "Dado restrito" quando a permissão não está demonstrada (§6.4.5).
4. **Padrão de abas.** A tela da escola tem abas (`tabs-row`, `tab-btn`, `tab-panel`, `role="tablist"`) em `apps/web/resources/views/escolas/show.blade.php:62-67`. Os botões de aba não têm `onclick`, mas o script de troca é inline na mesma view (bloco `<script>` nas linhas 543-553), o que o ADR-0023 não permite. Outros botões da view têm `onclick`. **Risco:** o estilo dessa aba fica preso a `.tela-escola` (`apps/web/public/css/app.css:449-454`) e usa texto branco com transparência, pensado para o cabeçalho em degradê. Fora dessa tela, o texto some, nos dois temas. Reutilizar essas classes não serve. **Base correta:** a tela de turma, em `apps/web/resources/views/turmas/_abas.blade.php` (`tu1-abas`, `data-turma-abas`), com `apps/web/public/js/turma-abas.js`, que é externo. **Requisito do mockup:** um componente comum de abas, derivado de `tu1-abas`, com setas, Home e End ([09 Interface e design system](../../arquitetura/09-interface-e-design-system.md)). O JavaScript fica em arquivo externo.
5. **Passagem para a Central do cliente.** A forma é **[Pendente]**: tela inteira ou modal. Nos dois casos, a passagem mostra o **destinatário** (pessoa, vínculo e conta contratante em rótulo de exibição) **antes** da saída, e o tipo da ação. A saída exige confirmação em modal da aplicação (CC-7). Estado 9 vale durante a passagem. Trocar de vínculo depois não muda o destinatário ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §4; condição B5).
6. **Retorno.** O retorno abre Plano e uso no vínculo ativo da sessão. Se o vínculo ativo é o de origem da intenção, a tela mostra os estados 10 a 13 como aviso no topo do Resumo. Se não é, mostra só o aviso neutro, sem dados do outro contexto. O retorno nunca troca o vínculo. Voltar da Central não prova pagamento (condição B6). O retorno vem da lista fechada do contrato 14 §13.
7. **Solicitação de ajuste** (uso livre monitorado): um pedido contextual, com o estado 14 depois do envio. A tela não edita limite (CC-8).
8. **Celular e desktop.** No celular, as abas ocupam a largura toda e os cartões empilham em uma coluna. A tabela do Extrato segue o padrão responsivo da FN-074. A conferência dos dois layouts é obrigatória (CC-6).
9. **Recurso futuro.** O que depende da Central ou do contrato externo e ainda não existe aparece como estado vazio ou "Em breve", sem módulo local paralelo. O app já usa "Em breve" em `apps/web/resources/views/home/_recursos.blade.php` e `components/painel-widget.blade.php`.

### 7.2 Menu **[Diretriz]** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2)

1. O destino-alvo é **Plano e uso no menu da conta**, como entrada única ao fim da transição.
2. Durante a transição, "Assinatura e cobrança" (legado) convive com a entrada nova. Hoje o legado está em `apps/web/resources/views/layouts/app.blade.php:46`, visível a `auth_titular` fora do contexto interno. O menu da conta é um `<nav aria-label="Opções da conta">` dentro de `#user-menu`, aberto pelo avatar (`layouts/app.blade.php:36-39`). O item legado não tem ícone. Os outros itens têm `x-icone-estrutura`. O item novo precisa de ícone (`x-icone-estrutura`) e de `aria-current` na página ativa.
3. **Nada muda no código agora.** Rota, menu e view ficam como estão. A regra de quem vê a entrada nova é **[Pendente]** (§6.4.1, item 5; condição B2).
4. Entradas contextuais **[Proposto]**, sempre com destinatário visível e sem compra local:
   - aviso no envio ao modo servidor da correção, quando o estado for 6, 7 ou 8;
   - aviso de limite atingido (estado 8), nunca no uso livre sem decisão (ADR-0062 §3);
   - aviso de falta de cobertura (estado 6), com o caminho permitido ao perfil.

### 7.3 Estados na composição **[Proposto]**

A lista de 14 estados e os textos-chave têm **um dono**: a seção "Estados" dos [casos de uso](casos-de-uso.md#referências-comuns-aos-casos-16-a-27). Esta seção não repete a tabela. Ela diz só como os estados aparecem na tela, com os componentes reais de `apps/web`:

| Grupo | Estados (N) | Como aparece |
|---|---|---|
| Página inteira | 4 (acesso negado) | `x-estado-tela` (`components/estado-tela.blade.php`), com título, mensagem e ação. O componente usa `<h1>`: serve só quando substitui a página |
| Carregando | 1 | `x-estado-tela` com `:carregando="true"` quando a rota inteira carrega (`role="status"`, `aria-live="polite"`, `aria-busy="true"`). Dentro de uma aba, `x-carregando` (`components/carregando.blade.php`), sem um segundo `<h1>` |
| Região da aba | 2, 5, 6, 7 | Bloco no lugar do conteúdo afetado, com título, corpo e ação. Os estados 2, 6 e 7 partem de `x-vazio` (`components/vazio.blade.php`). Estado 5 usa `role="alert"`. Estados 2, 6 e 7 usam `role="status"`. Os outros blocos da tela continuam visíveis quando a consulta deles responde |
| Aviso no topo do Resumo | 8, 9, 10, 11, 12, 13, 14 | Aviso com texto-chave e ação, com `x-aviso`. Estado 13 usa `role="alert"`. O estado 12 (cancelado, ação do próprio usuário) usa `status`, **[Proposto]**, a confirmar no mockup. Os demais usam `role="status"`. O aviso não esconde os dados do vínculo |
| Sucesso | 3 | Valores com unidade, origem e hora na mesma linha (CC-2) |

**Requisitos do mockup (subfase 2.3)** **[Proposto]**, conferidos no código:

- `x-carregando` (`components/carregando.blade.php`) não tem `role` nem `aria-live`. Dentro da aba, o mockup embrulha o componente numa região `role="status"`, ou propõe o ajuste no componente.
- `x-vazio` (`components/vazio.blade.php`) não tem `role`, usa `<b>` como título e não tem lugar de ação. O mockup define o invólucro `role="status"`, o lugar da ação e se o título precisa de nível de heading.
- `tu1-abas` (`turmas/_abas.blade.php`) usa `<a>` com `role="tab"` aplicado por JavaScript (`public/js/turma-abas.js:53`). O componente comum de abas decide entre âncoras e botões. Ele não muda o contrato de `aria-selected`, `aria-controls` e foco.
- Só os estados 5 e 13 usam `role="alert"`.

Regras: os estados 4 a 8 nunca se fundem. Cada um tem texto e ação próprios. O estado 5 não mostra valor antigo como atual. A mudança de estado é anunciada a leitores de tela pela região `status` ou `alert`. A cor nunca é o único sinal. **Os avisos reutilizam `x-aviso`** (`apps/web/resources/views/components/aviso.blade.php`; tipos sucesso, erro, atenção e info; `role="alert"` só no tipo erro e `role="status"` nos demais). O doc 09 lista o componente. O mockup não cria outra classe de aviso, e o `ui-ux` revisa.

### 7.4 Dados fictícios **[Proposto]**

Todo dado fictício do mockup traz a marca "demonstração" (CC-5). Nenhum número, nome de plano, ciclo, franquia ou valor vira regra comercial (CC-4). O mockup não mostra valor monetário do plano (decisão pendente, [ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md) §2, **[Pendente]**). Nomes de pessoas, escolas e turmas são inventados.

### 7.5 Mockup da subfase 2.3 **[Proposto]**

**Situação: a fazer na subfase 2.3 (mockup), que não está autorizada.** Esta seção só planeja.

- **Telas:** Resumo; Extrato; passagem para a Central do cliente; retorno com os estados 10 a 13; solicitação de ajuste em análise (estado 14). Cada tela cobre os estados que se aplicam a ela (7.3).
- **Perfis e vínculos de demonstração:**
  1. professor autônomo, com plano pessoal;
  2. professor em escola de uso livre monitorado;
  3. professor em escola de capacidade limitada;
  4. a mesma pessoa com dois vínculos, para a troca de vínculo (UC-068-18);
  5. representante institucional, com rótulo **[Pendente]**: o perfil não está definido (condição B2). A jornada fica "não verificável até a decisão".
- **Fora do mockup:** o perfil interno Financeiro (condição B3); gestão de limites e alocações; telas do Leitor360; compra, fatura e pagamento; qualquer saldo calculado.
- **Arquivos:** `mockups/plano-e-uso.html`, `mockups/css/plano-e-uso.css` e `mockups/js/plano-e-uso.js`. O executor é o `desenvolvedor-web`, restrito a esses três arquivos, **só com autorização escrita do responsável**.
- **Cobertura ([ADR-0057](../../decisoes/0057-app-responsivo-em-todas-as-faixas.md)):** **todas as telas, nas 11 larguras** (320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px) **e nos 2 temas**, com viewport real. A matriz reduz a redundância só nas combinações de perfil e estado, nunca em larguras ou temas.
- **Método de viewport real: [Pendente].** Definir antes da subfase 2.3. No levantamento de 12/11/2026, a redução de janela falhou como método.
- **Registro textual da conferência.** Cada linha traz: tela, perfil, vínculo, situação (número do estado), largura, tema, ação e resultado. Não há prints (ADR-0049).
- **Componentes do mockup:** abas comuns derivadas de `tu1-abas` (setas, Home e End), `x-aviso`, `x-carregando`, `x-vazio`, `x-tabela` e filtros do Extrato em modal. O item de menu novo leva ícone e `aria-current`.
- **Revisão:** o `ui-ux` revisa no navegador, em outro agente que não o autor, sem gravar screenshots no histórico da sessão (ADR-0049). A revisão de segurança é de outro agente.
- **Critérios:** CC-1 a CC-8 dos casos de uso. O mockup nunca fecha um critério marcado T ou E.

### 7.6 Mockup anterior (PR #275)

O mockup `mockups/financeiro.*` (PR #275, SHA `4a10ea2d`, branch `docs/fn-068-mockup-financeiro`) é **proposta superada por mudança de finalidade** ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md), Contexto). Ele não é defeito. O PR é fechado sem merge, e a branch e o SHA ficam preservados. Ele não é referência visual (ADR-0056, item 5).

## 8. Riscos e decisões pendentes do responsável

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| **Fonte da verdade do esquema `billing_`** | (a) Gabarito360 dono até o Core; (b) Core define e o Gabarito360 copia; (c) contrato de esquema externo | — | **Superada (12/11/2026).** O aplicativo financeiro é o dono. O Gabarito360 não tem esquema financeiro ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md)). A recomendação (a), registrada em 11/11, nunca foi aprovada |
| **Contratos, faturas, cobranças e notas fiscais do cliente institucional** (antigas F1 e F2) | (a) Ficam no aplicativo financeiro; (b) ficam no Gabarito360 como registro não financeiro | (a). A diretriz reserva pagamentos e auditoria financeira ao aplicativo externo. ⚠ Interpretação desta revisão: contratos, faturas e notas fiscais não aparecem por nome na diretriz | **Pendente** (confirmar) |
| **Papel do perfil interno Financeiro e do cadastro comercial** depois do aplicativo externo | (a) Só navegação ao aplicativo financeiro; (b) consulta limitada no Gabarito360; (c) manter como hoje | — | **Pendente.** O perfil não tem tela em Plano e uso (condição B3). A visão de vários clientes é do aplicativo externo. Nenhum caso de uso dá tela a esse perfil |
| **Lugar das ações humanas financeiras** (compra de créditos, limites por professor) | Painel externo, navegação integrada ou tela limitada no Gabarito360 | Nenhuma opção guarda credencial financeira global no Gabarito360 (contrato §8) | **Decidido (12/11/2026):** Central do cliente, no aplicativo externo (ADR-0062 §1). O provedor de identidade da Central é **[Pendente]** |
| **Achados de segurança no legado do Mercado Pago** | Registrar; investigar em tarefa própria | Registro apenas | **Registrados** por leitura de código, **não comprovados em produção**. A investigação é tarefa própria, sem expor segredos (ADR-0062 §7). Evidência no §6.3 e no contrato 14 §11 |
| **Quem vê o resumo institucional e quem abre a Central pela instituição** | Nenhum papel financeiro é deduzido de perfil pedagógico ou de direção (condição B2) | — | **Pendente.** O perfil do representante institucional continua sem definição. O aplicativo externo confirma o papel em cada ação |
| **Valor monetário do plano vigente** | Exibir ou não exibir | — | **Pendente** (ADR-0062 §2). Não é proibido nem exigido. Não bloqueia a documentação |
| **Comportamento além da referência de uso livre** | — | O Gabarito360 não infere bloqueio nem esgotamento (ADR-0062 §3) | **Pendente** |
| **Modelo de distribuição, reservas, fim de ciclo e professor sem limite** | — | — | **Pendente** |
| **Protocolo de identidade/SSO entre os produtos** | Login separado; federação; passagem de curta duração só para contexto, com autenticação própria da Central antes de ações que mudam estado | — | **Pendente** |
| **Mecanismo da intenção de travessia e destino de retorno** (condições B5 e B6) | O servidor cria a intenção e a liga de forma imutável a pessoa, vínculo, contexto e tipo de ação | — | **Pendente** (mecanismo). A regra está no contrato 14 |
| **Mecanismo de notificação** | — | — | **Pendente** |
| **Chave exata de permissão do detalhe pedagógico** (condição B4) | Só permissão existente com proteção demonstrada | Na dúvida, negar o detalhe ("Dado restrito") | **Pendente** até a demonstração |
| **Entidade de aplicação e formato do `correction_subject_id`** | Ver a [FN-081 §8](../081-creditos-correcao/planejamento.md), dona do item | — | **Pendente** |
| **Origem do evento de alteração de limite no extrato** | — | Alteração de limite não é consumo nem compra | **Pendente** no contrato |
| **Tela de passagem para a Central** | Tela inteira ou modal | — | **Pendente** |
| **Método de viewport real e forma da passagem** | Definir o método de conferência nas 11 larguras e a forma da passagem (tela inteira ou modal) | — | **Pendente.** Decidir antes da subfase 2.3 |
| **Finalidade do consumo por professor** (dado pessoal de funcionário) | — | A revisão de segurança **recomenda** proibir o uso do consumo por professor para avaliar desempenho. É recomendação da revisão, **não regra**, até a decisão do responsável | **Pendente** |
| **Log de acesso do nginx de `deploy/`** | `deploy/nginx/gabarito360.conf:10` grava a query string no log de acesso, sem formato próprio. A correlação da travessia leva valor na URL de retorno | Na implementação da travessia, usar o formato `g360_privado` (`infra/nginx.conf:11`) ou correlação de uso único. É sugestão para a infraestrutura, não decisão. Contrato [14 §13.2](../../arquitetura/14-integracao-financeira.md) | **Pendente.** A conferir no planejamento de implementação |
| **Aplicativo externo no [doc 11](../../arquitetura/11-seguranca-e-privacidade.md)** (terceiros, cláusulas de operador, RIPD) | — | Tarefa própria | **Pendente** |
| **Retenção** do registro operacional e da intenção de travessia | — | — | **Pendente** |
| **ADR-0007** (preços e Mercado Pago, linha 33; manter o código de assinatura e checkout enquanto a decisão 5 do plano mestre estiver aberta, linha 42) e **ADR-0013** (perfil Financeiro com meios de pagamento, linha 31) | O responsável decide se também são substituídos em parte | — | **Pendente** |
| **Cache das consultas externas; paginação e intervalo máximo do extrato** | — | — | **Pendente** (contrato externo) |
| **Destino do legado do MP-027** | Manter, migrar ou outro destino | Nada é desligado | **Pendente.** A investigar e decidir em tarefa própria (ADR-0062 §7) |
| **Piloto em produção com simulação** | (a) Não ter piloto; (b) piloto com o simulador em produção, com regras próprias | — | **Pendente.** Não habilitado. Sem decisão expressa, a produção não ativa o simulador |
| **Protocolo de contingência** | Ver o contrato §10 | Nenhuma autorização sem serviço disponível ou sem concessão válida | **Pendente** |
| **Conflito: "sem devolução de valores já pagos" × pendência jurídica 1** (arrependimento, CDC art. 49, reembolso e pacote parcialmente consumido) | (a) [RN-EIN-08](../../arquitetura/08-regras-de-negocio.md) vale só no escopo original (transição entre instituições); (b) estender à compra de créditos; (c) suspender a RN-EIN-08 até a revisão jurídica | (a). A compra de créditos por pessoa pode atrair o CDC art. 49. **Conflito registrado até a revisão jurídica.** Separar o aplicativo não encerra a pendência | **Pendente** |
| **Pendência técnica: entidade única de aluno + aplicação** (`correction_subject_id`) | Ver a [FN-081 §8](../081-creditos-correcao/planejamento.md), que é dona do item | — | **Pendente** |
| **Pendência jurídica 2: questões enviadas ao acervo** | Separar: contexto de autoria; titularidade e licença de uso; autorização de envio ao banco; direito de reutilização, adaptação e distribuição pelo Gabarito360; e a recompensa à pessoa | Licença não exclusiva de uso, adaptação e distribuição, com a autoria contextual preservada ([RN-PRV-16](../../arquitetura/08-regras-de-negocio.md) e [FN-038](../038-disciplinas-curriculo/planejamento.md)). Submeter à revisão jurídica, em especial na questão criada em contexto institucional. Separar o aplicativo não encerra a pendência | **Pendente** |
| **Quantidades e formato comercial dos créditos** (D-3 e D-4) | Ver a [FN-081 §8](../081-creditos-correcao/planejamento.md), que é dona dessas decisões | Decidir junto com os preços dos planos | **Pendente** |
| Autocadastro e SaaS (MP-027, MP-028, MP-029 e RN-GER-09) | Manter planejados ou declarar substituídos | Declarar o MP-028 e o checkout substituídos e o MP-029 obsoleto. Os preços ficam no aplicativo financeiro | **Pendente** |
| Condições de lançamento comercial e regras financeiras de adesão da escola autônoma | — | Tratar com as condições de [09 §2.3](../../arquitetura/09-interface-e-design-system.md). O fato financeiro (assunção paga) vem do aplicativo financeiro; a regra de acesso fica no Gabarito360 | **Pendente** |
| ⚠ Legado ativo além do `MercadoPagoService` | O plano mestre diz "sem código, só o `MercadoPagoService` legado". Estão ativos os itens do §6.3 | Decidir o destino em tarefa própria (subfase 3.1). Registrar no plano mestre. **Não desligar nesta reorientação** | **Pendente** (divergência entre plano mestre e código) |
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

**Entrega atual:** subfases **0.4** (adequação aos princípios da versão nova) e **2.2** (documentação de Plano e uso). Branch `docs/fn-068-plano-e-uso`. Nenhum código, mockup ou migration muda nesta entrega.

**Ordem até a entrega final:**

1. Aprovação documental pelo responsável (subfases 0.4 e 2.2).
2. Subfase 2.3, mockup funcional. **Não autorizada.** Exige autorização escrita do responsável.
3. Aprovação do mockup pelo responsável.
4. Planejamento de implementação. Sem código.
5. Implementação em pedidos próprios: subfase 2.4 (telas), 1.1 (porta e simulador) e 1.2 (cliente da API real, bloqueada pela dependência externa).

**Prioridade (decisão do responsável de 12/11/2026, [plano mestre](../../planejamento/00-plano-mestre.md) §4.1).** Só a FN-068 documental e as adequações da FN-081 têm prioridade. A aprovação desta documentação **não prioriza** a implementação da subfase 1.1 nem a da FN-081.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
| 2026-11-11 | Subfase 0.2 de adequação: cabeçalho no modelo atual; citações aos documentos antigos trocadas pelos destinos do mapa; RN-015 passou a RN-GER-09 (Substituída); interface em todas as faixas do ADR-0057; conciliação do legado com o esquema `billing_` do ADR-0061; fronteira com a FN-081 declarada; pendências novas no §8 | lucasgenial |
| 2026-11-12 | Subfase 0.3: reorientação para o aplicativo financeiro externo ([ADR-0061](../../decisoes/0061-arquitetura-de-billing-e-porta-billingclient.md), revisão de 12/11/2026). A FN passou a ser dona da experiência limitada, da integração e do legado. F1, F2 e F3 antigas e a conciliação com o esquema `billing_` foram superadas. O contrato passou ao [14 Integração com o aplicativo financeiro](../../arquitetura/14-integracao-financeira.md) | lucasgenial |
| 2026-11-12 | Simulador genérico da porta `BillingClient` como primeira entrega da integração (subfase 1.1): processamento educacional real, cenários configuráveis, estado fictício separado e ativação só em desenvolvimento e homologação; piloto em produção pendente | lucasgenial |
| 2026-11-12 | Regra do cliente: exibição temporária de dados autorizados, sem persistir; API real valida a mesma porta contra o contrato antes da troca (subfase 1.2); próxima entrega registrada (subfase 1.1) | lucasgenial |
| 2026-11-12 | Reorientação: a FN passou a ser "Plano e uso, integração financeira e legado" ([ADR-0062](../../decisoes/0062-plano-e-uso-e-central-do-cliente.md); decisões do responsável de 12/11/2026). Subfases 2.0 e 2.1 superadas (PR #275 fechado sem merge; proposta superada por mudança de finalidade). Subfases novas 0.4, 2.2, 2.3 e 2.4. Destino do legado pendente. Pendências e classe/modelo por papel registrados. Estrutura e seções 1 a 5 e 8 a 10 pelo `gerente-projetos`. O campo "Conforme princípios" fica na versão 18 até a revalidação da subfase 0.4 | lucasgenial |
| 2026-11-12 | §6 (contrato técnico) e o contrato [14](../../arquitetura/14-integracao-financeira.md) escritos pelo `arquiteto-software` | lucasgenial |
| 2026-11-12 | §6.4 (indicadores e extrato) escrito pelo `dados-dashboards` | lucasgenial |
| 2026-11-12 | §7 (fluxo e interface) escrito pelo `product-design` | lucasgenial |
| 2026-11-12 | Revisões independentes da subfase 2.2: `seguranca-privacidade` (reprovado até três correções de texto; depois aprovado com ressalvas), `qualidade-testes` (aprovado com ressalvas) e `ui-ux` (consulta prévia aprovada com ressalvas). Revisão independente de agente, não de fornecedor. Correções: estado do ADR-0062 ("Aceita no texto"), subfase 0.4 "Em andamento", pendências novas no §8, tabela de pareceres no §5 e rótulos da conversa trocados por remissões ao ADR-0062 | lucasgenial |
| 2026-11-12 | Subfase 0.4 entregue (documentação): `seguranca-privacidade` revalidou acesso, dados pessoais e privacidade na segunda leitura ("Pode subir"); "Conforme princípios" subiu de 18 para 19. Segundas leituras registradas: `seguranca-privacidade` e `qualidade-testes` (aprovado com ressalvas) e `ui-ux` (aprovado com ressalvas), independentes de agente, não de fornecedor. §8: finalidade do consumo por professor marcada como recomendação, não regra; linha nova para o log de acesso do nginx de `deploy/`. A subfase 2.2 continua em andamento até a aprovação do responsável | lucasgenial |
