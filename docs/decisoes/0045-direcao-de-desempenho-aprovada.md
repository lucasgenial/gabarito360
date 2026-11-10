# ADR-0045 — A direção de desempenho aprovada fecha o editor primeiro, mede a baseline autenticada e prioriza por evidência

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Direção de desempenho aprovada em 09/11/2026"); docs/192-fundamentos-desempenho.md
**Afeta:** [FN-077](../funcionalidades/077-desempenho/planejamento.md); [FN-054](../funcionalidades/054-paineis-editor/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Medições da homologação mostraram repetição de consultas e carregamento em duas etapas.

## Decisão

Aplique o checklist relevante à entrega atual do editor. Feche e valide sua release na homologação. Depois meça a baseline autenticada e priorize as otimizações por evidência.

Novas funcionalidades seguem os fundamentos do docs/192: a API é a fronteira do banco; o cache técnico tem escopo, versão e invalidação e não tem autoridade de acesso; a carga é progressiva; callbacks antigos são invalidados; as escritas usam integridade e idempotência adequadas.

## Consequências

- Teste e meça de forma proporcional ao risco. Metas sugeridas não são resultados atingidos.
- Não migre para React nem para outra infraestrutura. Não enfraqueça a segurança por inferência dessa direção.

## Verificação

- Os fundamentos formalizados estão no [ADR-0046](0046-fundamentos-de-desempenho-adr-195.md).
