# Campo

O campo `.field` agrupa rótulo, controle (`.input`, `.select`, `textarea`), ajuda e erro.

## Quando usar

- Use um `.field` por controle de formulário.
- Ligue o rótulo ao controle com `for` e `id`.
- Ligue a ajuda e o erro ao controle com `aria-describedby`.
- Gere o erro com `x-erro-campo`: ele cria `<span class="field-error" id="erro-{nome}">`.

## Variantes

| Parte | Legado (`LEG:170-182`) | TW2 em Turmas (`EST:112-116`) |
|---|---|---|
| `.field` | margem inferior 16 | margem inferior 16 |
| `label` | 14 px 600, margem 6 | 14/20 500, margem 8 |
| `.input`, `.select` | padding 11/14, borda 1,5 px `border`, raio 6, 14 px, fundo `#fff` (escuro `surface-2`) | altura 40, padding 8/12, borda 1 px, raio 6, fundo `surface`; 44 px em toque |
| Foco | borda `accent` + `0 0 0 3px rgba(29,79,158,.15)`, sem outline | `outline: 2px solid accent; offset 2px` |
| `.field-help` | 13 px `muted` | 12/20 `muted` |
| `.field-error` | só tem estilo dentro de `.troca-contexto-modal`, `.dc1-stack` e prova curricular | sem regra |
| `.input-icon` | ícone à esquerda, padding 40 | sem regra |

## Estados

- Foco: veja a tabela. O foco legado não usa `outline`.
- Erro: o texto do erro usa `danger` (500) onde existe regra. Fora disso, `.field-error` fica sem estilo (Divergências, item 24).
- Desabilitado: sem regra geral.

## Acessibilidade

- Todo controle tem rótulo visível.
- A borda `border` tem 1,53:1 sobre `surface` no claro e 1,39:1 no escuro. Fica abaixo de 3:1. O valor é do código e está sinalizado.
- Mostre o erro em texto, não só por cor.

## Regras por faixa

- Até 767 px, grades de formulário passam a 1 coluna (`LEG`, vários cortes).
- TW2: 44 px em `pointer:coarse` e até 767 px (`EST:127`).

ADRs: 0022, 0026.
