# Toast

O toast é uma notificação flutuante no canto superior direito, para sucesso, informação e tarefa em andamento.

## Quando usar

- Use para confirmar uma ação concluída ("Turma salva.") ou informar algo passageiro.
- Não use toast para erro de validação. Use `x-aviso` perto do controle (doc 09 §9).
- O app cria o toast por JavaScript (`resources/js/notificacoes.js`, biblioteca solid-toast). A marcação abaixo é a que o navegador recebe.

## Variantes

| Variante | Título padrão | Borda esquerda 4 px | Papel | Duração |
|---|---|---|---|---|
| `sucesso` | Concluído | `success` | `status`, polite | 5000 ms |
| `erro` | Não foi possível concluir | `danger` | `alert`, assertive | até fechar |
| `info` | Informação | `accent` | `status`, polite | 8000 ms |
| `carregando` | Em andamento | `accent` | `status`, polite | até fechar |

Não existe a variante "atenção" citada no doc 09 §9 (Divergências, item 21).

## Medidas

- Caixa 360 px, fundo `surface`, texto `fg`, borda `border-soft`, raio `radius-md`, sombra `shadow-md`, padding 12 (estilo em linha de `notificacoes.js:48`).
- Pilha `#g360-notificacoes`: fixa, `top: 72px + safe-area`, `z-index: 180`, gap 12, largura `min(376px, 100vw - 16px)`, no máximo 3.
- Fechar: 44 × 44 px, `aria-label="Fechar notificação"`.

## Estados

- Pausa com hover, foco e aba oculta. Escape fecha.
- Com um diálogo aberto, o toast vai para dentro do diálogo.
- Movimento reduzido: sem animação.

## Regras por faixa

- Até 640 px: largura 100%.
- Até 767 px: altura máxima menor, para não cobrir a navegação inferior.

ADRs: doc 09 §9.
