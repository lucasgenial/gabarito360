# ADR-0011 — Uma pessoa escolhe o vínculo ao entrar; a auditoria é restrita; o Suporte vê o sistema como outro usuário

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 4 "Já definidas" (vínculos, auditoria, ver como)
**Afeta:** [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md); [FN-004](../funcionalidades/004-auditoria/planejamento.md); [FN-005](../funcionalidades/005-suporte-ver-como/planejamento.md); [FN-013](../funcionalidades/013-acessos-internos/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O campo `usuarios.perfil` guardava um só perfil por conta. Uma pessoa não podia ser professora em uma escola e diretora em outra. O sistema também não tinha auditoria geral.

## Decisão

Decisões registradas:

- Pessoa com vários papéis escolhe o vínculo ao entrar.
- O Suporte pode ver o sistema como outro usuário. A decisão D9 (o "ver como" permite alterar dados?) continua pendente, com a recomendação "só leitura, como no ECOAR".
- Só Desenvolvedor e Suporte acessam a auditoria.
- Todos são auditados. O Suporte não vê registros de Desenvolvedores. Nenhum perfil está acima do Desenvolvedor.
- Perfis de escola e de núcleo são distintos. O professor autônomo é um perfil próprio.

## Consequências

- O "ver como" ([FN-005](../funcionalidades/005-suporte-ver-como/planejamento.md)) continua pendente de implementação e da decisão D9.
- O vínculo ativo acompanha a sessão (ver [ADR-0024](0024-vinculo-ativo-acompanha-a-sessao.md)).

## Verificação

- `apps/api/app/Support/ContextoVinculo.php` e o seletor de vínculo no login implementam a escolha.
- Os registros de auditoria existem na API (`apps/api/app/Support/Auditoria.php`). A tela de consulta é parcial.
