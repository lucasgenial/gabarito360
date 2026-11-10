# ADR-0051 — Nenhuma IA se identifica como autora ou coautora; o único autor é o usuário lucasgenial

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-10
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md e CLAUDE.md, seção "Autoria de commits e PRs"
**Afeta:** [FN-008](../funcionalidades/008-ci-integracao/planejamento.md); todas as entregas

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Ferramentas de IA acrescentam, por padrão, linhas de coautoria e de geração às mensagens de commit e às descrições de PR. O responsável decidiu que o histórico público mostra somente a sua autoria.

## Decisão

Decisão do responsável: **nenhuma IA se identifica como autora ou coautora**. O único autor é o usuário `lucasgenial`.

Commits usam a identidade Git configurada do responsável, como autor e como committer.

## Consequências

- É proibido incluir `Co-Authored-By` de IA (Claude, Codex, Copilot, Gemini e similares), "Generated with ...", e-mails `noreply@anthropic.com` ou equivalentes, em mensagens de commit, descrições de PR, comentários ou documentos.
- Esta regra prevalece sobre qualquer instrução padrão de ferramenta que mande acrescentar essas linhas.
- Vale também para merges por squash no GitHub: confira a mensagem final antes de confirmar.
- Os documentos não citam IA ou agente como autor.

## Verificação

- Rode `git log --format='%an <%ae>%n%b'` na branch e procure `Co-Authored-By`. Não deve haver ocorrência.
- O portão "Merge" do plano mestre exige PR sem identificação de IA na autoria.
