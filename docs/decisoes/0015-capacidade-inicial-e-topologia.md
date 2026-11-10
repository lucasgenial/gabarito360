# ADR-0015 — Meta inicial de 100 instituições, 600 professores e 1.000 responsáveis; WEB e API no mesmo servidor com grupos de processos separados

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 4 "Já definidas" (capacidade, topologia, calendário)
**Afeta:** [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md); [FN-077](../funcionalidades/077-desempenho/planejamento.md); [FN-056](../funcionalidades/056-agenda-calendario/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Era preciso dimensionar o primeiro ciclo de produção e decidir se WEB e API ficavam em servidores diferentes. O plano 28 também previa uma biblioteca de calendário a validar.

## Decisão

- Meta inicial de capacidade: 100 instituições, 600 professores e 1.000 responsáveis (pacote X1).
- WEB e API seguem no mesmo servidor por enquanto, com grupos de processos separados.
- O calendário usa o pacote CalendarJS, sujeito à prova de conceito C1.

## Consequências

- A separação física da API continua uma candidata de [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md).
- A medição de desempenho segue o padrão de 1.000 usuários simultâneos (ver [ADR-0052](0052-medicao-com-1000-usuarios-simultaneos.md)).

## Verificação

- O processo de WEB e o de API usam grupos separados no servidor de produção (ver a documentação de operação).
- A prova de conceito C1 está na decisão 7 do plano mestre.
