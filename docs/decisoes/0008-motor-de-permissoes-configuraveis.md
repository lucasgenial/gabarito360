# ADR-0008 — As permissões são um motor configurável por perfil e escola, migrado endpoint a endpoint

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-18
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/13-roadmap.md (MP-030, MP-031); decisão registrada em conversa de 18/06/2026
**Afeta:** [FN-012](../funcionalidades/012-permissoes-motor/planejamento.md); [FN-013](../funcionalidades/013-acessos-internos/planejamento.md); [FN-014](../funcionalidades/014-perfis-institucionais/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A tela `perfis-equipe` mostrou que a permissão por perfil precisava ser configurável. A necessidade estendeu-se ao professor titular de rede individual e ao aplicador. O motor deixou de ser tela cosmética e virou peça estrutural.

## Decisão

Criar um motor de permissões com catálogo de chaves, padrão por perfil e substituições por escola. A resolução segue esta ordem: substituição da escola, padrão do perfil e, por último, negar.

Fase 1 (MP-030): o motor roda em paralelo aos middlewares `perfil:...`, com os valores padrão iguais à matriz de perfis. Fase 2 (MP-031): substituir os middlewares fixos por `permissao:chave`, endpoint a endpoint e nunca em lote, com testes a cada lote.

## Consequências

- Toda rota da API usa o middleware `permissao:` com chave do catálogo de permissões.
- O middleware `perfil:...` só sai após todos os pontos de uso migrarem.
- Depois de mudar o catálogo, sincronize as permissões com o comando `g360:permissoes-sincronizar`.

## Verificação

- `apps/api/app/Support/CatalogoPermissoes.php` guarda o catálogo.
- `apps/api/routes/api.php` usa `permissao:` em cerca de 100 pontos e ainda usa `perfil:` em 8 (divergência: o MP-031 não terminou).
