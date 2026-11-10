# 030 — Autocadastro, planos e assinatura — Planejamento

**Estado no tronco:** Substituída (parcial)
**Prioridade no plano mestre:** sem posição nas ondas do §5; depende da decisão do §4 item 5. Preços e cobrança seguem para FN-068 (Onda 4).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-025..029; docs/06 RN-015/RN-016; docs/01 §30 (pivô SaaS de 18/06/2026)
**Atualizado em:** 2026-11-09, por lucasgenial

> Documento curto de funcionalidade substituída. Não decide o destino do código: a decisão é o §4 item 5 do [plano mestre](../../planejamento/00-plano-mestre.md).

## 1. Objetivo (original) e motivo da substituição
O pivô SaaS previa cadastro autônomo (individual e institucional), planos com preço, checkout pelo Mercado Pago e absorção de uma rede individual por uma institucional. O código adotou depois **contratação por contato** (PR #59) e o modelo cliente/espaço/vínculo (FN-021), com cadastro interno de clientes (FN-023), convites (FN-015) e espaço pessoal (FN-022).

## 2. O que existe hoje
- **MP-025 (entregue):** perfil de secretaria e `redes.modalidade`.
- **Planos:** tabelas `planos` e `assinaturas` (migrations de 19/06/2026); página pública `/planos`; `GET /api/v1/planos`.
- **Fale Conosco:** `/fale-conosco` (formulário, envio e página de agradecimento); `POST /api/v1/leads` (throttle 5/min). O formulário e o e-mail pertencem a FN-060.
- **Checkout desativado:** `/checkout` (GET e POST) redireciona para o Fale Conosco; `POST /api/v1/assinaturas/checkout` responde `409 'Para contratar, fale com nossa equipe pelo Fale Conosco.'`. Continuam registradas as páginas de retorno `/checkout/sucesso|falha|pendente` e o webhook `POST /api/v1/webhooks/mercadopago`.
- **Minha assinatura:** `/app/assinatura` e `POST /app/assinatura/cancelar`; `GET /api/v1/assinaturas/minha`, `POST /api/v1/assinaturas/cancelar`.
- **Não existe:** rota de autocadastro (MP-028); migração/absorção de rede individual (MP-029).

## 3. O que a substitui
| Necessidade | Funcionalidade |
|---|---|
| Entrada de novos usuários | Convites e vínculos — FN-015 (e FN-017 quando CPF-primeiro) |
| Cadastro do contratante | FN-023 (interno, por contato) |
| Professor individual | FN-022 / FN-027 |
| Escola contratante direta | FN-029 |
| Preços, contratos, cobrança | FN-068 |
| Levar dados do espaço individual para a instituição (MP-029) | FN-025 e FN-027 (PA1-C) |

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-060 email-transacional | Fale Conosco | Parcial |
| FN-068 financeiro | Preços (RN-015.1) e cobrança | Pendente |
| FN-072 site-publico | Página de planos e chamadas da home | Em homologação |

**Funcionalidades que dependem desta:** nenhuma.

## 5. Fases e subfases
| Subfase | Entrega | Status | Evidência |
|---|---|---|---|
| MP-025 secretaria e modalidade | Perfil e `redes.modalidade` | Entregue (produção) | commit "MP-025" |
| MP-027 planos, assinatura, checkout Mercado Pago | Planos e assinatura no código; checkout trocado por contato | Substituída por contratação por contato | `de68d3f` → PR #59 |
| MP-028 autocadastro | — | Substituída | convites (FN-015), T4 (FN-023), espaço pessoal (FN-022) |
| MP-029 absorção de rede individual | — | Pendente, provavelmente obsoleta | sem código |

## 6. Contrato técnico (resumo do existente)
`AssinaturaController` (API) consulta e cancela a assinatura do titular (`titular_id`); o cancelamento chama `MercadoPagoService` quando há assinatura mensal no gateway. Webhook verificado por HMAC. O teste de ponta a ponta com o sandbox do Mercado Pago nunca foi concluído.

## 7. Interface
Telas sem mockup (docs/01 §30): planos, Fale Conosco, retornos do checkout, Minha assinatura. Revisão visual pendente.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 5 — autocadastro e SaaS (MP-027/028/029, RN-016, docs/01 §30) | Declarar MP-028 e o checkout substituídos e MP-029 obsoleto; registrar no docs/06 a contratação assistida; preços em FN-068 / retomar autocadastro | A do plano mestre | Pendente |
| Código remanescente (webhook Mercado Pago, páginas de retorno, Minha assinatura, tabelas `planos`/`assinaturas`) | Manter para FN-068 / remover | Decidir junto com o item 5 | Pendente |
| Preços (RN-015.1), revisão visual das telas SaaS, RN-016.3 (permissões elevadas do titular) | — | Tratar em FN-068 | Pendente |
| docs/06 RN-016 e docs/01 §30 ainda descrevem autocadastro e checkout | — | Nota "superado pelo plano mestre" (Onda 0) | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
