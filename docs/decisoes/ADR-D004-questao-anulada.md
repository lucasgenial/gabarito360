# ADR-D004 - Politica de questao anulada

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + Pedagogico
- **Prazo:** resolvida; aplicar antes da implementacao de gabaritos

## Contexto

Resultados precisam ser calculados de forma consistente quando uma questao e anulada.

## Decisao

No MVP, uma questao anulada concede sua pontuacao integral a todos os resultados validos da prova, independentemente da resposta do aluno.

- A questao recebe situacao `anulada`.
- Seu peso compoe `pontos_possiveis`.
- O mesmo peso compoe `pontos_obtidos`.
- Ela incrementa `anuladas`, mas nao incrementa `acertos`, `erros`, `brancos` ou `duplas`.
- A politica aplicada deve ser registrada no resultado.

Alteracao de anulacao depois do inicio de aplicacoes segue o fluxo futuro de nova versao de gabarito e recorrection auditada.

## Justificativa

A regra e simples, previsivel e evita prejudicar alunos por problema na questao.

## Impactos

- O calculo deve preservar snapshot do peso e da situacao.
- Relatorios devem exibir anuladas separadamente.
- O gabarito publicado continua imutavel no MVP.
