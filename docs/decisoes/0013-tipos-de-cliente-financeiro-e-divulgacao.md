# ADR-0013 — Cliente pode ser professor, escola, núcleo, rede, secretaria ou governo; o Financeiro é um perfil; a divulgação depende do plano

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 4 "Já definidas" (clientes, Financeiro, selo)
**Afeta:** [FN-021](../funcionalidades/021-clientes-espacos/planejamento.md); [FN-023](../funcionalidades/023-cadastro-clientes/planejamento.md); [FN-068](../funcionalidades/068-financeiro/planejamento.md); [FN-069](../funcionalidades/069-selo-documentos/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O modelo de clientes e espaços (pacotes T1 a T6) exigia definir os tipos de cliente. O financeiro e o selo dos documentos precisavam de regras comerciais.

## Decisão

- Cliente pode ser professor, escola, núcleo, rede, secretaria ou governo.
- Cliente presenteado exibe a divulgação do Gabarito360 em impressões e PDFs. Cliente pagante exibe apenas "Feito no gabarito360.com.br".
- Existe um perfil Financeiro, com arquitetura pronta para vários meios de pagamento. A integração real fica para depois.

## Consequências

- O selo dos documentos ([FN-069](../funcionalidades/069-selo-documentos/planejamento.md)) usa essa regra de divulgação.
- A decisão D13 (dados da empresa para os termos) continua pendente.

## Verificação

- Consulte [FN-021](../funcionalidades/021-clientes-espacos/planejamento.md) e [FN-023](../funcionalidades/023-cadastro-clientes/planejamento.md) para o modelo de clientes e espaços.
- [FN-068](../funcionalidades/068-financeiro/planejamento.md) e [FN-069](../funcionalidades/069-selo-documentos/planejamento.md) estão pendentes de implementação.
