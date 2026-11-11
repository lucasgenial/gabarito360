# Rodapé de versão

O rodapé `.rodape-app` mostra o texto literal da versão do software no fim da área logada, a partir de 768 px.

## Quando usar

- Ele vem do layout e só existe com `release.sha` configurado.
- Texto literal: `Gabarito360 - v0.1.0 - Homologação` (ADR-0038).

## Medidas

- Fixo no fim, de `left: var(--lateral)` até a direita; altura mínima 44; padding 12/32; 12/20 `muted`; fundo `bg`; borda superior `border`; `z-index` 40 (`EST:70`).
- Barras de salvar sobem 44 px quando há rodapé (`LEG:1328`).

## Regras por faixa

- Até 767 px: oculto, sem reserva de espaço (ADR-0044).

ADRs: 0037, 0038, 0044.
