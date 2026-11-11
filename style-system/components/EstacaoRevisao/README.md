# Estação de revisão

A estação de revisão mostra uma questão duvidosa por vez, com o recorte da linha no cartão, as alternativas como botões e o cartão-resposta completo ao lado.

## Quando usar

- Na tela Revisar leituras (`correcao/revisao.blade.php`, `body.tela-revisao`).
- A fila tem as questões com marcação incerta ou sem marcação. A pessoa escolhe a alternativa, Em branco ou Anulada.

## Partes

| Parte | Regra | Fonte |
|---|---|---|
| Estação | grade `minmax(0,1.2fr) minmax(310px,1fr)`, gap 24 | `LEG:594` |
| Topo | nome do aluno em negrito, turma, e "Questão N · Faltam M" (14 px `muted`) | `LEG:620-621` |
| Recorte | linha da questão na foto endireitada, borda `border-soft`, raio `radius-md`, cópia bloqueada | `LEG:606-607,622` |
| Leitura | "Leitura do sistema: B e C (marcação incerta)", 14 px `muted` | `LEG:624` |
| Alternativas | grade com uma coluna por alternativa, gap 8, botões de 48 px; contorno e texto `accent`, 16 px 600 | `LEG:625-627` |
| Resposta atual | fundo `accent-light`, borda 2 px, barra inferior `accent` | `LEG:628` |
| Em branco, Anulada | `.rev-resposta-extra`, fundo `surface-2`, borda `muted` | `LEG:629,635` |
| Voltar, Pular | `.btn-secondary.btn-sm`, centrados | `LEG:637-638` |
| Teclas | "Teclado: A a E respondem, 0 deixa em branco e X anula." 12 px `muted` | `LEG:658` |
| Painel do cartão | sticky, altura `100dvh - 240px`, leitor com ferramentas de 44 px e área `surface-2` | `LEG:608-617` |

## Estados

- Salvando: botões desabilitados, opacidade .7, cursor `wait`; `#rev-salvamento` anuncia (`role="status"`).
- Fim da fila: "Revisão concluída" com Voltar à última e Voltar à correção.
- Foco das alternativas: `outline: 3px solid accent; offset 3px`.

## Regras por faixa

| Largura | Comportamento | Fonte |
|---|---|---|
| 1100 px ou mais | cartão completo no painel ao lado | `LEG:597,618` |
| até 1099 px | sem painel; botão "Visualizar gabarito" abre o cartão em `dialog.rev-cartao-modal` | `LEG:619,639` |
| até 1023 px | dica de teclado oculta | `LEG:661` |
| até 767 px | modal do cartão quase em tela inteira (16 px de margem) | `LEG:657` |
| até 400 px | gap das alternativas cai para 4 | `LEG:636` |

## Texto

- O botão "Visualizar gabarito" abre o cartão do aluno, não o gabarito oficial. O glossário e a FN-079 mandam trocar o rótulo por "Ver cartão inteiro". Pendência registrada em Divergências.

A marcação do topo, das alternativas e das ações é gerada por `js/correcao-revisao.js:162-176`; a prévia usa dados fictícios e o recorte sem imagem.

ADRs: 0023, doc 09 §9, FN-046, FN-079.
