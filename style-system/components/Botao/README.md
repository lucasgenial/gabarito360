# Botão

O botão `.btn` dispara uma ação da tela; a aparência depende da família de estilo da tela (legado ou TW2).

## Quando usar

- Use `.btn-primary` para a ação principal da tela ou do diálogo. Use um por região.
- Use `.btn-secondary` para ações de apoio, como Cancelar, Ver e Editar.
- Use `.btn-ghost` para ações de baixo peso, como Limpar campos.
- Use `.btn-success` só para publicar ou concluir.
- Use `.g360-arquivar-acao` para Arquivar e Desarquivar. Mantenha o texto (ADR-0034).
- Não crie botão com `onclick`. Ponha o JavaScript em arquivo externo (ADR-0023).

## Duas famílias

O mesmo nome de classe tem aparência diferente. A tela decide a família pelo `<body>`.

| Classe | Legado (todas as telas, exceto Turmas) | TW2 (`body.tela-turmas`, `body.tela-turma`) |
|---|---|---|
| `.btn` | 14 px 600, padding 10/20, raio 6, borda 1,5 px (`LEG:151-167`) | altura 32 px, padding 4/12, 14/20 500, borda 1 px (`EST:104`) |
| `.btn-primary` | fundo `accent`, texto `accent-on`; hover `accent-dark` | igual, borda `accent`; hover `brightness(.96)` |
| `.btn-secondary` | contorno e texto `accent`; hover `accent-light` | fundo `surface`, texto `fg`, borda `border` |
| `.btn-ghost` | texto `fg-2`; hover `surface-2` | texto `muted` |
| `.btn-success` | fundo `success`, texto branco (4,59:1) | fundo `success-light`, texto e borda `success` |
| `.btn-danger` | só em `#retirar-modal` | fundo `danger-light`, texto `danger` |
| `.btn-sm` | padding 6/12, 13 px | sem regra |
| `.btn-block` | largura 100% | sem regra |
| `:disabled` | sem regra geral | opacidade .5, cursor `not-allowed` |

## Estados

- Hover e active: veja a tabela.
- Foco: a casca usa `outline: 2px solid accent; outline-offset: 2px`. Não remova o contorno.
- Desabilitado: só o TW2 tem regra geral. No legado, cada tela define o seu.

## Acessibilidade

- Botão só com ícone exige `aria-label` e `title`.
- O ícone fica com `aria-hidden="true"`. O nome acessível fica no botão (ADR-0033).
- Dê ícone e texto a Editar turma e a Arquivar turma (ADR-0025).

## Regras por faixa

- TW2: 32 px fora do toque; 44 px em `pointer:coarse` e até 767 px (`EST:127`).
- Legado: cerca de 38 px de altura. Garante 44 px só em listas definidas (revisão, resultado, troca de vínculo). Isto é uma lacuna (Responsivo, item 6).
- Ações de `x-pagina` se adaptam ao contêiner (veja Página).

ADRs: 0023, 0025, 0026, 0034.
