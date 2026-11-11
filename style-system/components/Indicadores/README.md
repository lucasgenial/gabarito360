# Indicadores (KPI)

Os indicadores mostram números-chave em cartões: `x-indicadores` com `x-indicador` nos painéis novos, e a faixa `.kpi-grid` nas telas legadas.

## Quando usar

- Use `x-indicadores` e `x-indicador` em tela nova.
- Cada indicador tem rótulo, valor e, quando útil, um detalhe ("matrículas ativas").
- Use o tipo só para reforçar a situação. Escreva a situação no detalhe.
- Não invente números. Use dados do cadastro (doc 09 §2.1).

## O que o consumidor fornece

`<x-indicador rotulo="Alunos" valor="54" detalhe="matrículas ativas" tipo="sucesso|perigo|destaque" />`.

## Variantes

| Variante | Regra | Fonte |
|---|---|---|
| `.g-indicadores` | grade `auto-fit minmax(min(200px,100%),1fr)`, gap 16 | `LEG:892` |
| `.g-indicador` | fundo `surface`, borda `border-soft`, raio `radius-lg`, padding 16/20; rótulo 14 `muted`; valor 28 px 800; detalhe 12 `muted` | `LEG:893-899` |
| Tipos | `sucesso` (valor em `success`), `perigo` (`danger`), `destaque` (`accent`) | `LEG:892-899` |
| `.kpi-grid` legado | uma faixa só, sem gap, borda `border-soft`, raio 8; cards separados por sombra de 1 px; valor 28 px | `LEG:279, 1014-1021` |
| `.kpi-trend.up` / `.down` | 13 px 600 em `success` / `danger` | `LEG:214-215` |

## Contraste

- `success` sobre `surface` no escuro: 3,48:1. `danger` sobre `surface` no escuro: 2,90:1. O valor tem 28 px 800 (texto grande), mas o `kpi-trend` de 13 px fica abaixo de 4,5:1 no escuro. Pares mantidos do código.

## Regras por faixa

- `.kpi-grid`: 2 colunas até 767 px; a última ímpar ocupa a linha.
- Ajuda no título do indicador: só nos widgets de painel (veja Ajuda no título).

ADRs: doc 09 §2.1, §7.
