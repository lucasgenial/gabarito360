---
id: ADR-0033
titulo: Rodada 3 da prévia: contexto em largura total, ícones locais e menu lateral
estado: Substituída
substituida_por: [ADR-0039, ADR-0036, ADR-0063]
data: 2026-11-08
afeta_funcionalidades: []
afeta_caminhos: []
verificacao: processo
regras: []
---
# ADR-0033 — Rodada 3: contexto em largura total, Material Symbols locais e menu lateral claro e navy

**Atualizado em:** 13/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Substituída por [ADR-0039](0039-coluna-de-acoes-a-direita-e-ordenacao.md) (ações à esquerda), [ADR-0036](0036-tw2-filtros-em-modal-e-superficie-escura.md) (cor escura da lateral) e [ADR-0063](0063-familia-de-icones-propria-em-fonte.md) (ícones Material Symbols); o restante continua vigente
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Rodada 3"); docs/186, seção "Rodada3"
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A rodada 3 revisou a prévia antes da implementação real.

## Decisão

Decisões da rodada 3:

- O `contexto-espaco` ocupa toda a largura, sem breadcrumb reservando faixa lateral.
- O Meu espaço na lateral expandida fica fixo, sem `details`/`summary`. A variante do card continua recolhível.
- Menu lateral branco no tema claro e navy `#001534` no escuro.
- **Tabela:** Ações é a primeira coluna real, à esquerda, com largura intrínseca limitada e botões `pq`. **Cards:** ações no rodapé esquerdo, alinhadas na linha, sem altura fixa.
- Material Symbols Outlined locais, sem CDN, com ícones 16 nas ações `pq` e 20 na navegação. Preserve a licença e use nomes acessíveis contextuais.

## Consequências

- A coluna de ações da tabela passou para a direita ([ADR-0039](0039-coluna-de-acoes-a-direita-e-ordenacao.md)). As ações dos cards continuam no rodapé esquerdo.
- O navy `#001534` da lateral no escuro foi substituído pela superfície da homologação ([ADR-0036](0036-tw2-filtros-em-modal-e-superficie-escura.md)).
- A parte "Material Symbols Outlined locais" foi substituída pela família própria G360 Icones ([ADR-0063](0063-familia-de-icones-propria-em-fonte.md)), em 13/11/2026.
- Meu espaço fixo na lateral e contexto em largura total continuam vigentes.

## Verificação

- Procure referências a CDN de fontes de ícones nos templates. Não deve haver nenhuma.
