# Aviso

O aviso `x-aviso` mostra uma mensagem em linha, junto aos controles, com cor e papel por tipo.

## Quando usar

- Use para erro e conflito, perto do controle que os causou (doc 09 §9).
- Use toast para sucesso e informação passageira.
- Não mude as cores semânticas do aviso (ADR-0022).

## Marcação

`<x-aviso tipo="sucesso|erro|atencao|info">texto</x-aviso>` gera `<div class="g-aviso {tipo}" role="…">`. O tipo `erro` recebe `role="alert"`; os outros, `role="status"`.

## Variantes

Base: borda 1 px, raio `radius-md`, padding 12/16, 14 px 600, `margin-block: 16px` (`LEG:904`).

| Tipo | Fundo | Texto | Borda | Contraste do texto |
|---|---|---|---|---|
| `sucesso` | `success-light` | `success` (escuro: `texto-sucesso`) | `success` | Fraco no claro: 4,02:1. Escuro: 11,07:1. |
| `erro` | `danger-light` | `danger` (escuro: `texto-erro`) | `danger` | 4,66:1 claro; 9,66:1 escuro |
| `atencao` | `warn-light` | `warn-fg` | `warn` | 6,79:1 claro; 9,48:1 escuro |
| `info` | `accent-light` | `accent-dark` | `accent` | 8,89:1 claro; 7,35:1 escuro |

## Estados

- Mensagem de sessão (flash): recebe `data-g360-flash`. O toast equivalente oculta o aviso e tira o `role`.
- Sem margem dentro de `.grid`, `.side-stack`, `.stack-grid`, `.main-grid`, `.content-grid`, `.dc1-stack` e no início e fim de `.card-pad`.

## Acessibilidade

- `role="alert"` só no erro.
- Escreva a causa e a ação: "Não foi possível salvar. Confira o nome da turma."

ADRs: 0022.
