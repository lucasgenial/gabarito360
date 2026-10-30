# Plano: fotos sob demanda na correção

**Problema:** em 30/10/2026, a tela de correção pediu as 47 fotos de uma vez. Cada foto ocupa um processo PHP da web e outro da API, no mesmo grupo. Os processos se esgotaram e o site parou.

**Decisão:** web e API continuam no mesmo grupo de processos. A correção reduz a carga criada pela tela.

## Ações

1. Trocar as miniaturas da lista de corrigidos pelo botão "Ver foto". A imagem só é pedida ao clicar.
2. Espaçar as consultas automáticas: situação a cada 10 s e lista a cada 20 s. Com a aba em segundo plano, as consultas param.
3. Usar a espera padrão de 15 s para a foto, no lugar de 180 s.
4. Mostrar uma mensagem quando a foto não abrir.

## Limite conhecido

Muitos pedidos simultâneos ainda podem esgotar os 20 processos, porque a web depende da API no mesmo grupo. Separar os grupos fica como opção futura.
