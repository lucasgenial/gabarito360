# Estado de tela

O componente `x-estado-tela` ocupa a área de conteúdo para mostrar um erro de página (401, 403, 404, 419, 429, 500, 503) ou um carregamento de página.

## Quando usar

- Use em `layouts/estado.blade.php` e em `resources/views/errors/*`.
- Não ponha o estado dentro de um card.
- Ofereça uma ação segura, por exemplo "Ir para o painel".

## O que o consumidor fornece

- `titulo` (obrigatório), `mensagem`, `codigo`, `carregando` (bool) e o slot de ações.
- Com `carregando`, o componente recebe `role="status"`, `aria-live="polite"` e `aria-busy="true"`.

## Medidas

| Parte | Regra | Fonte |
|---|---|---|
| Bloco | centro da tela, largura máxima 36rem | `estado-tela.css:3-11` |
| Título | `clamp(22px, 2vw, 28px)` | idem |
| Código | 13 px 600, caixa alta, espaçamento .08em | idem |
| Ações | gap 12, altura mínima 44 | idem |
| Carregando | marca 72 px (64 até 480), pulso 1,2 s por 3 ciclos; título 16 px 500 | `estado-tela.css:13-21` |

## Regras por faixa

- Até 480 px: ações em coluna, com largura total.

ADRs: doc 09 §6.
