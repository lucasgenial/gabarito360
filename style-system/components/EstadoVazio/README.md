# Estado vazio

O componente `x-vazio` diz que uma lista ou região ainda não tem registros, com ícone, título e uma explicação curta.

## Quando usar

- Use quando a coleção está vazia de verdade.
- Vazio, sem resultado (filtro sem retorno) e erro são estados diferentes. Não mostre erro como vazio (doc 09 §5.1).

## O que o consumidor fornece

- `titulo` (obrigatório), `icone` (nome de `x-icone`; padrão `prova`) e o texto no slot.

## Medidas

- Centro, padding 40/20, cor `muted`; ícone 36 px; título 16 px em `fg-2` (`LEG:913-915`).

## Exemplo de texto

- Título: "Nenhuma prova".
- Explicação: uma frase com a próxima ação. Exemplo: "Crie uma prova para começar a corrigir."

ADRs: doc 09 §5.1.
