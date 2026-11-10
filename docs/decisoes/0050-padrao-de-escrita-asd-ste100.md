# ADR-0050 — Todo documento novo ou revisado segue o padrão ASD-STE100 moderado, aplicado ao português

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-10
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md e CLAUDE.md, seção "Padrão de escrita dos documentos (ASD-STE100 moderado)"
**Afeta:** Toda a documentação

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A documentação cresceu em estilos diferentes e com frases longas. O responsável pediu um padrão curto e uniforme, legível por pessoas e por IA.

## Decisão

Decisão do responsável: todo documento novo ou revisado segue o padrão ASD-STE100 (Simplified Technical English) em nível moderado, cerca de 80% das regras, aplicado ao português.

1. Escreva uma ideia por frase.
2. Limite a frase de procedimento a 20 palavras e a descritiva a 25.
3. Use voz ativa. Em procedimentos, use o verbo no imperativo.
4. Escreva uma instrução por frase. Use listas numeradas para passos e tabelas para comparações.
5. Use um termo para cada conceito, no documento inteiro.
6. Defina as siglas na primeira ocorrência. Evite pronomes vagos.
7. Coloque os avisos antes da instrução a que se referem.
8. Limite os parágrafos a 6 frases.

## Consequências

- Exceções (os 20% restantes): nomes de código, citações literais, termos de domínio sem equivalente simples e tabelas de referência.
- Documentos históricos (`docs/NN-*.md`) não são reescritos só para seguir o padrão. O padrão vale quando um documento for criado ou revisado.

## Verificação

- Revise as frases do documento contra os limites de 20 e 25 palavras.
