# Visor da câmera

O visor `.cam-tela` ocupa a tela inteira com a imagem ao vivo da câmera, as guias dos quatro quadrados pretos e o botão de disparo.

## Quando usar

- Só pela ação Fotografar cartões do Card da câmera.
- O visor é preto em qualquer tema: a imagem da câmera manda.

## Partes

| Parte | Regra | Fonte |
|---|---|---|
| Tela | fixa, `inset: 0`, `z-index` 1000, fundo `#000`, `touch-action: none` | `LEG:857` |
| Imagem | `canvas` que ocupa o espaço; o `video` fica oculto | `LEG:859` |
| Topo | dica 14 px 600 branca sobre degradê preto 65%; Fechar 44 px | `LEG:860-862` |
| Base | lanterna, disparo e Auto sobre degradê preto 70% | `LEG:863` |
| Disparo | 74 px, borda 5 px branca, fundo branco 25% | `LEG:864-865` |
| Botão redondo | 44 × 44, círculo, fundo branco 18% | `LEG:862` |
| Último envio | `.cam-ultimo`, caixa branca 95% acima da base; some quando vazia | `LEG:867-868` |

## Comportamento

- `js/correcao-camera.js` desenha as guias no `canvas`. Cada canto fica verde quando o quadrado está no lugar.
- Com os 4 cantos verdes, a mão firme e Auto marcado, a foto é tirada sozinha.
- A lanterna só aparece quando o aparelho a oferece (`visibility: hidden` no início).

## Acessibilidade

- Fechar, Lanterna e Tirar foto têm `aria-label`.
- Divergências: Fechar usa o caractere ✕, não `x-icone`; as cores do visor são valores fixos sem token.

A prévia é estática: sem câmera, o `canvas` fica preto e as guias não aparecem.

ADRs: doc 18.
