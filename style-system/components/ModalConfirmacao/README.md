# Modal de confirmação

O modal de confirmação pede uma decisão antes de uma ação com efeito, como arquivar uma turma; mostra o nome do alvo e o efeito.

## Quando usar

- Use antes de arquivar, desarquivar, excluir ou retirar.
- Nunca use `confirm()`, `alert()` nem `prompt()` (ADR-0025).
- Mostre o nome do registro e o efeito em frases curtas. Exemplo real: "O histórico será preservado e o cadastro sairá das listas de ativos."

## Marcação

O exemplo é o diálogo real de `x-arquivamento`: `<dialog class="tu1-dialog g360-arquivar-dialog">` aberto por `showModal()` em `js/arquivamento.js`.

## Medidas

| Parte | Regra | Fonte |
|---|---|---|
| Caixa | `min(540px, 100vw - 32px)`, padding 24, borda `border`, raio 12, fundo `surface`, sombra `shadow-md` | `arquivamento.css:1` |
| Backdrop | `#00153480` (valor fixo, sem token) | `arquivamento.css:1` |
| Fechar | `.btn-ghost` com ícone, 44 × 44 px | `arquivamento.css:1` |
| Registro | negrito 700, margem 20/12 | `arquivamento.css:1` |
| Ações | à direita, gap 12, botões de 44 px | `arquivamento.css:1` |
| Confirmar | fundo `danger`, texto branco (5,49:1) | `arquivamento.css:1` |

Outros modais do app usam medidas diferentes: modal legado `.modal-backdrop` (520 px, raio 8, backdrop `rgba(0,0,0,.45)`), modal TW2 de Turmas (raio 12, fechar 44 px), troca de vínculo (640 px, raio 8). Veja Divergências.

## Estados

- Escape e Cancelar fecham e devolvem o foco ao botão de origem (ADR-0036).
- O foco inicial vai para o diálogo.

## Acessibilidade

- `aria-labelledby` no título e `aria-describedby` no efeito.
- Fechar só com ícone tem `aria-label="Fechar confirmação"`.

## Regras por faixa

- Até 600 px: padding 20; ações em coluna invertida e com largura total (Confirmar fica no topo).

ADRs: 0025, 0034, 0036.
