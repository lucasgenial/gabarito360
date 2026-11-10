# 068 — Financeiro (contratos, faturas, pagamentos) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 4 (PROPOSTA do §5): 068 financeiro → 069 selo; depende de decisão comercial
**Natureza:** nova, com legado de assinatura (MP-027) existente documentado a partir do código
**Origem:** tronco original — docs/28 F1–F3 (Fase 5); docs/163 (regras financeiras de EI1); docs/176 (FIN). Equivalência: F1–F3 e FIN → FN-068 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-10, por lucasgenial

## 1. Objetivo
Dar à equipe do Gabarito360 (perfil Financeiro) o controle comercial de cada cliente: contrato, itens, faturas, cobranças, pagamentos e notas fiscais, com lançamento manual desde o início e arquitetura pronta para vários meios de pagamento. O Financeiro gerencia um cliente sem acessar dados de alunos.

## 2. Escopo
- **Inclui:**
  - F1: tabelas `contratos`, `contrato_itens`, `faturas`, `cobrancas`, `pagamentos`, `notas_fiscais`, `eventos_pagamento`; migração de `planos`, `assinaturas` e `pagamentos` atuais trocando titular por cliente; valores em centavos; cliente público (processo, empenho, vigência, nota fiscal); desconto, cortesia e modalidade `presenteado`.
  - F2: telas do Financeiro no cadastro do cliente (contratos, faturas, baixa manual, inadimplência) e painel de recebimentos; bloqueio por inadimplência só depois de regra definida.
  - F3: interface `GatewayPagamento` (criar cobrança, consultar, cancelar, estornar, validar webhook), `GatewayManual`, adaptação do `MercadoPagoService`, webhook único por provedor com assinatura verificada, eventos pela fila sem repetir efeito.
  - Regras de docs/163: assunção financeira da escola autônoma pela instância superior por fluxo auditável; **sem devolução** de valores já pagos; alcance e vigência da assunção; período já pago; encerramento posterior.
  - Preços (RN-015.1) e condições de lançamento comercial (docs/164), conforme o §4 itens 5 e 14.
- **Não inclui:**
  - Cadastro de clientes e `contrato_referencia` em texto → [FN-023](../023-cadastro-clientes/planejamento.md) (entregue).
  - Autocadastro, checkout público e MP-029 → [FN-030](../030-saas-autocadastro-assinatura/planejamento.md) (substituída em parte; decisão §4 item 5).
  - Selo dos documentos por modalidade → [FN-069](../069-selo-documentos/planejamento.md).
  - Adesão da escola autônoma (EI1-E) → [FN-029](../029-escola-independente/planejamento.md); encerramento de cliente (T6) → [FN-028](../028-encerramento-cliente/planejamento.md).
  - E-mail `assinatura-pagamento` → [FN-060](../060-email-transacional/planejamento.md) (futuro).

**Regra fixa do docs/28:** nunca guardar número de cartão ou CVV; só tokens do provedor.

## 3. Fontes de verdade
- Mockups: não há mockup das telas do Financeiro; páginas de planos, checkout e "Minha assinatura" constam no docs/01 (`docs/01-inventario-dos-mockups.md`) (seção SaaS) como novas, sem mockup.
- Documentos de origem: docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) Fase 5 e decisões já definidas (perfil Financeiro, presenteado/pagante); docs/163 (`docs/163-escolas-independentes.md`) (regra comercial adicional); docs/176 (`docs/176-reconciliacao-solicitacoes.md`) (FIN); docs/164 (`docs/164-apresentacao-publica-pre-lancamento.md`); docs/06 (`docs/06-regras-de-negocio.md`) RN-015.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`), extensão A1 (Financeiro: consultar e gerir clientes; dados pedagógicos negados).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão comercial (§4 itens 5 e 14) | Preços, autocadastro/checkout, condições de lançamento, regras de adesão | Pendente |
| FN-021 clientes-espacos (T2) | Contrato pertence ao cliente | Concluída |
| FN-023 cadastro-clientes (T4) | Telas do Financeiro ficam no cadastro do cliente | Concluída |
| FN-013 acessos-internos (A1) | Perfil Financeiro e contexto interno | Concluída |
| FN-004 auditoria (AU1) | Trilha das operações financeiras | Parcial |
| FN-002 base-operacao (B4 filas) | Eventos de pagamento pela fila | Concluída |

**Funcionalidades que dependem desta:** FN-069 (selo, na ordem da Onda 4), FN-029 (EI1-E), FN-028 (T6), FN-023 (contratos completos), FN-030 (preços RN-015.1).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (MP-027) Legado de assinatura | Tabelas `planos`, `assinaturas`, `pagamentos`; `MercadoPagoService`; `GET /planos`; `assinaturas/minha` e `cancelar`; webhook Mercado Pago; checkout desativado (409, contratação por contato) | — | Substituída em parte (código ativo) | commit de68d3f; PR #59; `AssinaturaController`, `WebhookController`, `ContratacaoContatoTest` |
| 1.1 (F1) Modelo financeiro | Migrations, models, migração do legado, centavos, cliente público, cortesia | Testes cobrem contrato, fatura e pagamento manual | Pendente | Sem migrations de contratos/faturas; `docs/36-financeiro.md` previsto colide com o docs/36 existente |
| 2.1 (F2) Telas do Financeiro | Contratos, faturas, baixa manual, inadimplência, painel de recebimentos | Financeiro gerencia um cliente sem acesso a dados de alunos | Pendente | — |
| 3.1 (F3) Interface de pagamentos | `GatewayPagamento`, `GatewayManual`, adaptação do Mercado Pago, webhook por provedor, fila idempotente | Um provedor falso de teste passa por todo o fluxo | Pendente | Só o `MercadoPagoService` legado |

## 6. Contrato técnico (proporcional ao risco)
- API dona do banco; migrations aditivas; a migração de `assinaturas`/`pagamentos` (hoje `decimal(10,2)` e ligados a `titular_id`, `rede_id`, `secretaria_id`) para cliente e centavos precisa de prévia e reversão.
- Autorização: somente contexto interno (`ContextoInterno`, hoje `cliente.ver` e `cliente.gerir` para o Financeiro); novas chaves financeiras a aprovar. Nenhuma rota financeira expõe dado pedagógico.
- Webhooks: assinatura verificada, idempotência por identificador do evento, processamento pela fila.
- Sem dados de cartão; só tokens e identificadores do provedor.
- Auditoria de toda baixa, estorno, cortesia e assunção financeira.
- Volume baixo (meta de 100 instituições); listas paginadas.

## 7. Interface
Telas no contexto interno, junto de `clientes/*`; estados carregando, vazio e erro; desktop e 375 px; tabelas no padrão de FN-074. Sem mockup: exige desenho e aprovação antes da F2. Conferência por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 5 — autocadastro e SaaS (MP-027/028/029, RN-016) | Manter planejados / declarar substituídos | Declarar MP-028 e o checkout substituídos, MP-029 obsoleto, registrar a contratação assistida no docs/06; os preços (RN-015.1) entram nesta funcionalidade | Pendente |
| §4 item 14 — condições de lançamento comercial (docs/164) e regras financeiras de adesão da EI1 | — | Tratar neste planejamento | Pendente |
| ⚠ Legado ativo além do `MercadoPagoService` | O plano mestre diz "sem código, só o `MercadoPagoService` legado", mas estão ativos: tabelas `planos`/`assinaturas`/`pagamentos`, `POST /webhooks/mercadopago` (público, HMAC), `GET /assinaturas/minha`, `POST /assinaturas/cancelar` (chama o gateway), web `/assinatura` e `/checkout/*` | Decidir na F1/F3: desativar o webhook e o cancelamento até a F3, ou mantê-los; registrar no plano mestre | Pendente (divergência plano mestre × código) |
| Rotas `assinaturas/*` sem middleware de permissão | Só autenticação; filtram por `titular_id` | Rever na F1 | Pendente |
| Regras ainda não especificadas de docs/163 | Adesão da instância superior, alcance e vigência da assunção, período já pago, encerramento | Não inventar crédito, prorrata ou vencimento; levar ao responsável | Pendente |
| Bloqueio por inadimplência (RN-015.4 × docs/28 F2) | RN-015.4 define bloqueio após 7 dias; docs/28 diz "só depois de regra definida" | Vale o docs/28 até nova decisão | Pendente |
| Nome do documento `docs/36-financeiro.md` | Colide com o docs/36 existente | Este `planejamento.md` substitui o documento previsto | Registrado |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
