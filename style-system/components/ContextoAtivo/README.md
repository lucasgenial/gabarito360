# Contexto ativo (Meu espaço)

O componente `x-contexto-ativo` mostra o espaço e o perfil em uso: recolhível no topo do conteúdo e fixo na lateral expandida.

## Quando usar

- Variante recolhível (`details.contexto-ativo`) no topo do conteúdo.
- Variante fixa (`fixo`) no rodapé da lateral expandida (ADR-0033).
- Não crie botão nem modal "Meu espaço" no cabeçalho (ADR-0029).

## Rótulos possíveis

Minha escola, Minha rede, Meu núcleo, Minha secretaria, Tipo de espaço, Espaço ativo, Área de acesso (`CMP/contexto-ativo:6-9`).

## Medidas

- Resumo: 44 px, 14 px 500; a seta gira 90° ao abrir (`EST:49-52`).
- Dados: duas colunas `minmax(0,1fr) minmax(96px,auto)`, fundo `surface-2`, raio 6, padding 12 (`EST:53`).
- Erro de troca: 12/18 `muted`, `role="status"`.

## Regras por faixa

- Até 767 px: dados em 1 coluna.
- Impressão: oculto.

ADRs: 0029, 0032, 0033.
