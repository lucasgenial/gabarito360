# ADR-0049 — Nenhum print da aplicação é mantido, em nenhum serviço; as conferências visuais são registradas por texto

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md e CLAUDE.md, seção "Prints e imagens da aplicação"
**Afeta:** [FN-070](../funcionalidades/070-identidade-visual/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-008](../funcionalidades/008-ci-integracao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Capturas de tela acumulavam em repositórios, PRs e serviços de imagem e podiam expor dados de alunos.

## Decisão

Por padrão, **nenhum print da aplicação é mantido**. "Print" abrange screenshot e captura de tela de qualquer origem, inclusive as feitas por agentes e ferramentas de navegador (Chrome, browser pane, Computer Use, Playwright, CI).

Nenhum print fica no repositório nem em qualquer outro serviço de imagens: PRs, issues, documentos, artifacts, Drive, Canva e similares.

## Consequências

- Registre as conferências visuais por texto: o que foi verificado, onde, em que tema e largura, e o resultado.
- Screenshots usados durante a conferência servem só para olhar. Não os salve em disco, não os anexe nem os publique. Apague as cópias locais deixadas pelas ferramentas ao fim da conferência.
- Imagens versionadas só são aceitas como assets da aplicação (`apps/web/public/`, `apps/web/resources/images/`) ou como desenhos de referência (`mockups/`, `style-system/`).
- Uma exceção exige autorização expressa do responsável para aquele caso.

## Verificação

- A CI executa `python3 scripts/verificar_imagens.py` (`.github/workflows/verificacao.yml`).
