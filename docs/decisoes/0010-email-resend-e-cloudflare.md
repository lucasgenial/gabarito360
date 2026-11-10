---
id: ADR-0010
titulo: E-mail com Resend para envio e Cloudflare Email Routing para recebimento
estado: Aceita
substituida_por: []
data: 2026-10-31
afeta_funcionalidades: [FN-011, FN-060]
afeta_caminhos: []
verificacao: processo
regras: []
---
# ADR-0010 — O e-mail usa Resend para envio e Cloudflare Email Routing para recebimento, com quatro endereços

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 4 "Já definidas" (decisões de e-mail)
**Afeta:** [FN-060](../funcionalidades/060-email-transacional/planejamento.md); [FN-011](../funcionalidades/011-autenticacao-conta/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Em 31/10/2026, o domínio `gabarito360.com.br` bloqueava todo e-mail (`MX 0 .`, SPF `-all`, DMARC `p=reject`). A recuperação de senha gravava o token e não enviava nada. O plano 28 adotou o modelo já usado no projeto ECOAR.

## Decisão

Usar Resend para envio e Cloudflare Email Routing para recebimento, como no ECOAR. Os endereços `contatos@` e `suporte@` encaminham ao mesmo destino usado em outros domínios do responsável.

| Endereço | Uso | Direção |
|---|---|---|
| `nao-responda@` | Confirmação e recuperação de senha | Só envia |
| `notificacoes@` | Avisos | Só envia |
| `contatos@` | Fale Conosco | Recebe |
| `suporte@` | Atendimento | Recebe |

## Consequências

- Não envie e-mail real para pessoas fora da equipe sem aprovação (ver [ADR-0009](0009-limites-de-agentes-e-conclusao-de-pacote.md)).
- A decisão D15 (acesso à Cloudflare) era pendente para o pacote E1. O pacote E1 foi concluído.

## Verificação

- A API e a WEB configuram o envio pelos arquivos `config/mail.php` e `config/services.php`. Confirme o mailer de cada ambiente sem expor chaves.
- O Fale Conosco grava e envia a mensagem ([FN-060](../funcionalidades/060-email-transacional/planejamento.md)).
