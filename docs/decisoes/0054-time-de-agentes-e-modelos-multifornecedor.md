---
id: ADR-0054
titulo: Time de agentes e modelos de IA de mais de um fornecedor
estado: Aceita
substituida_por: []
data: 2026-11-10
afeta_funcionalidades: [todas]
afeta_caminhos: [".claude/agents/**", "docs/planejamento/**", "AGENTS.md"]
verificacao: revisao-ia
regras: []
agents_secao: Agentes e modelos de IA
agents_ordem: 10
agents_resumo:
  - "O time de agentes, os papéis, a matriz por tipo de subfase e as regras de passagem estão em `docs/planejamento/02-agentes.md`."
  - "Quem implementa não revisa a própria entrega. As revisões de UI/UX e de segurança são sempre de outro agente."
  - "Cada papel usa uma classe de modelo (raciocínio alto, equilibrado, econômico). A tradução para os modelos da Anthropic e da OpenAI está em `docs/planejamento/03-modelos-de-ia.md`. O agente `selecao-modelos` recomenda o modelo de cada tarefa."
  - "As definições para o Claude Code ficam em `.claude/agents/`. As regras do AGENTS.md valem para todas as ferramentas."
---
# ADR-0054 — O projeto é operado por um time de agentes, com modelos da Anthropic e da OpenAI

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-10
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** pedido do responsável em 10/11/2026, na reorganização da governança
**Afeta:** todas as funcionalidades; [02-agentes.md](../planejamento/02-agentes.md); [03-modelos-de-ia.md](../planejamento/03-modelos-de-ia.md)

## Contexto

O projeto usa vários agentes de IA e mais de uma ferramenta. O responsável usa modelos da Anthropic (Claude Code) e da OpenAI (Codex) no mesmo projeto. Sem regra comum, cada ferramenta escolhe o modelo e o fluxo por conta própria. O custo em tokens também cresce sem controle.

## Decisão

O projeto é operado por um time de agentes. Os papéis, a matriz por tipo de subfase e as regras de passagem estão em `docs/planejamento/02-agentes.md`.

- Quem implementa não revisa a própria entrega. As revisões de UI/UX e de segurança são sempre de outro agente.
- Cada papel usa uma classe de modelo: raciocínio alto, equilibrado ou econômico.
- O projeto usa modelos da Anthropic e da OpenAI. A tradução de cada classe para os modelos de cada fornecedor está em `docs/planejamento/03-modelos-de-ia.md`.
- O agente `selecao-modelos` recomenda o modelo de cada tarefa.
- As definições em `.claude/agents/` são a implementação para o Claude Code. O `AGENTS.md` vale para todas as ferramentas.

Palavras do responsável: "Deve ter um agente que escolhe o melhor modelo para o desenvolvimento, etc... Eu falo isso pois utilizo no mesmo projeto as LMMs da Antropic e da OpenAI."

Sobre a economia: "esses agentes tem que utilizar o melhor e mais economico modelo de IA que vc tiver, para evitar gastar muito token", "mas ao mesmo tempo ser efetivo."

## Consequências

- Toda tarefa recebe um papel e uma classe de modelo antes de começar.
- Nenhum agente aprova a própria entrega.
- Trocar de modelo ou de fornecedor exige só atualizar `03-modelos-de-ia.md`.
- Uma regra nova de agentes entra no `AGENTS.md`, não só nas definições do Claude Code.

## Verificação

O agente de conformidade confere se cada entrega indica o papel, a classe de modelo e um revisor diferente do implementador. A regra vale também para `.claude/agents/`.
