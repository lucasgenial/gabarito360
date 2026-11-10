# ADR-0007 — O produto vira SaaS multi-tenant com secretaria, modalidade individual e institucional, cadastro autônomo e assinatura

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-18
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/13-roadmap.md (MP-025 a MP-029, Nota de Sequenciamento); decisão registrada em conversa de 18/06/2026
**Afeta:** [FN-021](../funcionalidades/021-clientes-espacos/planejamento.md); [FN-022](../funcionalidades/022-espaco-pessoal/planejamento.md); [FN-030](../funcionalidades/030-saas-autocadastro-assinatura/planejamento.md); [FN-068](../funcionalidades/068-financeiro/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O responsável decidiu transformar o Gabarito360 em SaaS multi-tenant. O roadmap inspirou-se no modelo individual ou organização do ZipGrade e nas organizações de produtos como Slack e Notion. O plano original de fechamento de gaps foi reordenado e expandido.

## Decisão

Decisões do roadmap revisado em 18/06/2026:

- Criar o nível **Secretaria** acima da rede. Criar a modalidade de rede `institucional` ou `individual` (MP-025).
- Criar os perfis Secretário de Educação e Aplicador. O aplicador só captura e envia cartões (MP-026).
- Cobrar por assinatura com Mercado Pago. Cinco planos: Professor Mensal R$ 9,99; Professor Anual R$ 99,90; Instituição até 10 licenças R$ 7,50 por licença por mês; Instituição acima de 10 licenças e Secretaria/Núcleos em modo comercial, sem preço de tabela (MP-027).
- Planos comerciais caem no "Fale Conosco" e nunca no checkout (MP-027).
- Cadastro autônomo individual e institucional substitui a aprovação manual (MP-028). A interface da rede individual não exibe os níveis rede e núcleo.
- A absorção de rede individual por rede institucional é irreversível por autoatendimento e exige suporte para desfazer (MP-029).

## Consequências

- Os MPs 025 a 027 foram implementados. Os MPs 028 e 029 ficaram pendentes.
- O catálogo marca [FN-030](../funcionalidades/030-saas-autocadastro-assinatura/planejamento.md) (autocadastro, planos e assinatura) como Substituída. A decisão 5 do plano mestre, que declara o autocadastro e a assinatura online como substituídos, continua aberta.
- Enquanto a decisão 5 estiver aberta, não remova o código de assinatura nem de checkout.

## Verificação

- Existem as views `assinatura/index` e `checkout/index` em `apps/web/resources/views`.
- Consulte a decisão 5 em `docs/planejamento/00-plano-mestre.md`, seção 5.
