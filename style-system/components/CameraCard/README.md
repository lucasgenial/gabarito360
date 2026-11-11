# Card da câmera

O card da câmera inicia a correção pela foto: Fotografar cartões abre o visor ao vivo e Enviar fotos da galeria envia várias fotos de uma vez.

## Quando usar

- No acompanhamento da prova e no modo só-câmera do aplicador (`correcao/_camera.blade.php`).
- Mostre a instrução curta: "Inclua a folha inteira e os quatro quadrados pretos."
- Ponha a explicação longa em `details` ("Saiba mais sobre a foto e a leitura").

## Partes

| Parte | Regra |
|---|---|
| Card | `.card.card-pad`, margem superior 16 (estilo em linha) |
| Fotografar | `.btn-primary` com ícone `camera`, padding 16, 16 px, `flex: 1 1 220px` (estilo em linha) |
| Galeria | `label.btn.btn-secondary` ligado a `input[type=file][multiple]` oculto |
| Câmera do sistema | `input[type=file][accept=image/*][capture=environment]` oculto, para navegador sem câmera ao vivo |
| Resumo | `#envios-resumo.page-sub` com `role="status"` |
| Envios | `ul#envios` com `aria-live="polite"`; itens preenchidos por `js/cartoes-fila.js` |

## Estados

- Sem câmera ao vivo (navegador antigo ou permissão negada): o app usa a câmera do sistema.
- Os envios aparecem na lista, um por cartão.

## Texto

- Termos: "cartão" e "cartão-resposta" para a folha do aluno; "gabarito" só para as respostas oficiais (glossário).
- Receber a foto não é concluir a leitura. O texto do "Saiba mais" diz isso.

## Divergência

O card usa estilos em linha (`style=`). Não é um componente Blade com classe própria.

## Regras por faixa

- Os dois botões ficam lado a lado e quebram em linha abaixo de cerca de 460 px de largura útil (`flex: 1 1 220px`).

ADRs: 0023, doc 18.
