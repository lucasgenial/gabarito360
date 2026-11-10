# ADR-0032 — O menu desktop alterna entre expandido (256 px) e compacto (80 px); celulares até 767 px usam navegação inferior

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Rodada 2 da prévia"); docs/186, seção "Rodada2"
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A primeira rodada recolhia o menu desktop sem deixar coluna. O responsável pediu a lógica do YouTube, sem copiar o visual.

## Decisão

Regras da rodada 2:

| Item | Regra |
|---|---|
| Menu desktop | Alterna entre expandido (256 px) e compacto (80 px). Ambos refluem o conteúdo. |
| Marca | Marca completa apenas no cabeçalho, com ativo claro no escuro. Sem logo duplicada. |
| Tablets | A partir de 768 CSS px usam a estrutura desktop otimizada. |
| Celulares | Até 767 px usam navegação inferior de 4 atalhos mais Mais, e folha inferior ou lista vertical, nunca lateral desktop. |
| Meu espaço no celular | Empilha escola, nome, perfil, nome. No desktop usa duas colunas. |
| Teste | Preserve a variante do card. Teste a variante nova no rodapé da lateral expandida, com divisor acima. |
| Avatar de entidade | Classe própria, 64 px no desktop e 56 px no mobile. |
| CSS | Separe as fontes de CSS mobile e desktop. |

O FormValidation foi autorizado expressamente pelo responsável, com licença e atualização gratuita documentadas. Não copie documentos privados de compra para o Git nem para arquivos públicos.

## Consequências

- Este pedido supera o recolhimento sem coluna da rodada anterior.
- Continue somente a prévia até a aprovação visual geral.

## Verificação

- Verifique 256 px e 80 px de largura do menu nos arquivos de CSS do layout.
