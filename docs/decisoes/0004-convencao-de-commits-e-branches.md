---
id: ADR-0004
titulo: Commits em tipo(escopo) pt-BR, atômicos, em branches de feature
estado: Aceita
substituida_por: []
data: 2026-06-16
afeta_funcionalidades: [todas]
afeta_caminhos: []
verificacao: processo
regras: []
agents_secao: Publicação
agents_ordem: 10
agents_resumo:
  - "A `main` é protegida: toda mudança entra por PR, com os 6 jobs da CI aprovados e merge por squash. Não há envio direto nem forçado, inclusive para o administrador."
  - "Não inclua em commits arquivos de outras tarefas."
---
# ADR-0004 — Commits seguem tipo(escopo): descrição em pt-BR, são atômicos e nascem em branches de feature

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-16
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** CLAUDE.md, seção "Convenção de Commits"; docs/15-plano-de-commits.md (convenção e regras de commit)
**Afeta:** Todas as funcionalidades; [guia de contribuição](../README.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O monorepo recebe trabalho de várias frentes. Uma convenção única mantém o histórico legível e facilita a revisão.

## Decisão

Formato: `tipo(escopo): descrição curta em pt-BR`, com corpo e rodapé opcionais.

| Tipos | feat, fix, docs, refactor, test, chore, db, style |
|---|---|
| Escopos | api, web, android, ios, docs, db, infra, omr, relatorio |

Regras do docs/15:

1. Escreva em português do Brasil.
2. Limite a linha do título a 72 caracteres.
3. Separe o corpo por uma linha em branco e explique o porquê.
4. Faça um commit por funcionalidade atômica, não por arquivo.
5. Nunca versione `.env`, credenciais, arquivos de senha nem binários grandes.
6. Trabalhe em branches de feature, não direto na `main`.

Atualização de 10/11/2026 (FN-078, subfase 1.1): a `main` é protegida no GitHub. Toda mudança entra por PR. O merge exige os 6 jobs da CI aprovados e usa squash. O envio direto e o envio forçado são bloqueados, também para o administrador.

## Consequências

- Mensagens em inglês, sem tipo ou vagas ("Update files") são incorretas.
- A autoria dos commits segue o [ADR-0051](0051-autoria-exclusiva-do-responsavel.md): nenhuma IA aparece como autora ou coautora.
- Os exemplos de commits por MP do docs/15 são histórico e não definem trabalho futuro.

## Verificação

- Revise `git log` da branch: título com tipo e escopo, até 72 caracteres.
- O PR usa o modelo `.github/pull_request_template.md`.
