# Cabeçalho

O cabeçalho `.cabecalho-app` é a barra única de 64 px no topo da área logada, com recolher menu, marca, tema e o avatar da conta.

## Quando usar

- Sempre, uma vez por página. Ele vem do layout `layouts/app.blade.php`.
- Use o avatar só para o menu da conta. Não ponha navegação principal no menu do avatar (ADR-0030).
- Não crie botão nem modal "Meu espaço" no cabeçalho (ADR-0029).

## Partes

| Parte | Regra | Fonte |
|---|---|---|
| Barra | sticky no topo, `z-index` 50, padding 8/16, gap 12, fundo `surface`, borda inferior `border` | `EST:9` |
| `.controle-estrutura` | 32 × 32, raio 6, fundo transparente; hover `surface-2` | `EST:13-14` |
| Marca | `x-marca` sm (ícone 32, nome 18) | `MAR:15` |
| Tema | alterna `data-theme` no `<html>` e grava `g360-theme`; ícone lua (claro) ou sol (escuro), 18 px | `public/js/app.js:158-181` |
| Avatar da conta | 40 × 40, círculo, borda `border`, fundo `accent-light` | `EST:17-18` |
| Menu da conta | 288 px, 12 px abaixo do avatar, raio 8, sombra `0 8px 24px #00153420`; itens de 40 px; Sair em `danger` | `EST:20-29` |
| Trocar vínculo | só com mais de um vínculo; abre `dialog#troca-contexto-modal` | `layouts/app.blade.php:41-44` |

## Estados

- Menu da conta: Escape fecha; clique fora fecha.
- Foco: `outline: 2px solid accent; outline-offset: 2px` em toda a casca (`EST:16`).

## Regras por faixa

- Até 767 px: botão recolher oculto; gap 8; controles com 44 × 44.
- Em `pointer:coarse`: controles e itens do menu da conta com 44 px.

ADRs: 0029, 0030, 0032, 0034.
